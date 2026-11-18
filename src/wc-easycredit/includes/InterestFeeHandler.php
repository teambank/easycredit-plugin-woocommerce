<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit;

/**
 * Centralized handling of the EasyCredit interest fee (name, add/remove on orders).
 */
class InterestFeeHandler
{
    /** Fee line item name for EasyCredit interest (untranslated). */
    const FEE_NAME_INTEREST = 'Interest';

    /** Stable cart fee id (locale-independent). */
    const FEE_ID = 'easycredit-interest';

    const TEXT_DOMAIN = 'wc-easycredit';

    /**
     * Whether a fee line item is the EasyCredit interest fee (by name only).
     *
     * @param \WC_Order_Item_Fee|\WC_Order_Item $fee_item
     * @return bool
     */
    public static function is_interest_fee_item($fee_item): bool
    {
        if (!$fee_item || !is_callable([$fee_item, 'get_name'])) {
            return false;
        }
        $name = $fee_item->get_name();

        return self::matches_interest_fee_name($name);
    }

    /**
     * Whether a cart fee line (WC_Cart_Totals wrapper or fee props) is the interest fee.
     *
     * @param object $fee
     */
    public static function is_interest_cart_fee_line($fee): bool
    {
        if ($fee instanceof \WC_Order_Item_Fee) {
            return self::is_interest_fee_item($fee);
        }

        if (isset($fee->id) && (string) $fee->id === self::FEE_ID) {
            return true;
        }

        $props = isset($fee->object) ? $fee->object : $fee;

        if (isset($props->id) && (string) $props->id === self::FEE_ID) {
            return true;
        }

        $name = null;
        if (isset($props->name)) {
            $name = (string) $props->name;
        } elseif (is_object($props) && is_callable([$props, 'get_name'])) {
            $name = (string) $props->get_name();
        }

        if ($name !== null) {
            return self::matches_interest_fee_name($name);
        }

        return false;
    }

    /**
     * @param string $name
     */
    public static function matches_interest_fee_name(string $name): bool
    {
        return $name === self::get_interest_fee_name() || $name === self::FEE_NAME_INTEREST;
    }

    /**
     * Display name for the EasyCredit interest fee (translated).
     *
     * @return string
     */
    public static function get_interest_fee_name(): string
    {
        return __('Interest', self::TEXT_DOMAIN);
    }

    /**
     * Add the EasyCredit interest fee to an order if it's not already present.
     *
     * @param \WC_Order $order
     * @param float     $interest_amount
     */
    public static function add_to_order(\WC_Order $order, float $interest_amount): void
    {
        foreach ($order->get_items('fee') as $fee_item) {
            if (self::is_interest_fee_item($fee_item)) {
                return; // already on the order; a new order-pay start removes it before the next amount is added
            }
        }

        $item_fee = new \WC_Order_Item_Fee();
        $item_fee->set_name(self::get_interest_fee_name());
        $item_fee->set_tax_status('none');
        $item_fee->set_tax_class('0');
        $item_fee->set_amount($interest_amount);
        $item_fee->set_total($interest_amount);
        $item_fee->set_total_tax(0);
        $item_fee->set_taxes(['total' => []]);
        $order->add_item($item_fee);
        self::recalculate_order_totals($order);
        self::clear_interest_fee_taxes_on_order($order);
        $order->save();
    }


    /**
     * Add the EasyCredit interest fee to the cart (e.g. when returning from payment page).
     * Caller should pass the interest amount from storage; no recalc is done here.
     *
     * @param float $interest_amount
     */
    public static function add_to_cart(float $interest_amount): void
    {
        if (!WC()->cart || $interest_amount <= 0) {
            return;
        }

        foreach (WC()->cart->get_fees() as $fee) {
            if (self::is_interest_cart_fee_line($fee)) {
                return;
            }
        }

        WC()->cart->fees_api()->add_fee([
            'id' => self::FEE_ID,
            'name' => self::get_interest_fee_name(),
            'amount' => $interest_amount,
            'taxable' => false,
        ]);
    }

    /**
     * Strip split taxes German Market or Germanized may have applied to the interest fee line.
     */
    public static function clear_interest_fee_taxes_on_order(\WC_Order $order): void
    {
        $changed = false;

        foreach ($order->get_items('fee') as $fee_item) {
            if (!self::is_interest_fee_item($fee_item)) {
                continue;
            }

            if ((float) $fee_item->get_total_tax() !== 0.0) {
                $fee_item->set_total_tax(0);
                $fee_item->set_taxes(['total' => []]);
                $fee_item->save();
                $changed = true;
            }
        }

        if ($changed) {
            self::recalculate_order_totals($order);
        }
    }

    /**
     * Remove all EasyCredit interest fee line items from an order (by name),
     * then recalculate and save totals.
     *
     * @param \WC_Order $order
     * @return bool True if at least one fee was removed
     */
    public static function remove_from_order(\WC_Order $order): bool
    {
        $removed = false;

        foreach ($order->get_items('fee') as $item_id => $fee_item) {
            if (self::is_interest_fee_item($fee_item)) {
                // Use the WC_Order API so the in-memory items cache stays in sync.
                $order->remove_item($item_id);
                $removed = true;
            }
        }

        if ($removed) {
            self::recalculate_order_totals($order);
            $order->save();
        }

        return $removed;
    }

    /**
     * Rebuild the order total after a fee change without losing shipping tax.
     *
     * A full recalculation asks WooCommerce to recompute shipping tax. Germanized
     * then replaces that result and, on an already saved order, can leave the net
     * shipping amount with the tax cleared. The financed total was taken before
     * this runs, so the shipping lines are put back when that happens.
     */
    private static function recalculate_order_totals(\WC_Order $order): void
    {
        $shipping_before = self::shipping_tax_snapshot($order);
        $order->calculate_totals();

        if (!self::restore_wiped_shipping_taxes($order, $shipping_before)) {
            return;
        }

        if (is_callable([$order, 'update_taxes'])) {
            $order->update_taxes();
        } else {
            $shipping_tax = 0.0;
            foreach ($order->get_items('shipping') as $item) {
                $shipping_tax += (float) $item->get_total_tax();
            }
            $order->set_shipping_tax($shipping_tax);
        }

        // Taxes are already on the lines; only the grand total needs summing.
        $order->calculate_totals(false);
    }

    /**
     * @return array<int, array{total: float|string, total_tax: float|string, taxes: array}>
     */
    private static function shipping_tax_snapshot(\WC_Order $order): array
    {
        $snapshot = [];

        foreach ($order->get_items('shipping') as $item_id => $item) {
            if (!$item instanceof \WC_Order_Item_Shipping) {
                continue;
            }

            $snapshot[(int) $item_id] = [
                'total' => $item->get_total(),
                'total_tax' => $item->get_total_tax(),
                'taxes' => $item->get_taxes(),
            ];
        }

        return $snapshot;
    }

    /**
     * @param array<int, array{total: float|string, total_tax: float|string, taxes: array}> $snapshot
     */
    private static function restore_wiped_shipping_taxes(\WC_Order $order, array $snapshot): bool
    {
        $restored = false;

        foreach ($order->get_items('shipping') as $item_id => $item) {
            if (!$item instanceof \WC_Order_Item_Shipping || !isset($snapshot[(int) $item_id])) {
                continue;
            }

            $saved = $snapshot[(int) $item_id];
            if ((float) $saved['total_tax'] <= 0.0 || (float) $item->get_total_tax() !== 0.0) {
                continue;
            }

            $item->set_total($saved['total']);
            $item->set_taxes($saved['taxes']);
            $item->save();
            $restored = true;
        }

        return $restored;
    }
}
