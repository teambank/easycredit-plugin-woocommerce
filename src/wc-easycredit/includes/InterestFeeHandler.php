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
                return; // fee already present (amount may have changed)
            }
        }

        $item_fee = new \WC_Order_Item_Fee();
        $item_fee->set_name(self::get_interest_fee_name());
        $item_fee->set_amount($interest_amount);
        $item_fee->set_total($interest_amount);
        $order->add_item($item_fee);
        $order->calculate_totals();
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

        WC()->cart->add_fee(
            self::get_interest_fee_name(),
            $interest_amount,
            false
        );
    }

    /**
     * Remove all EasyCredit interest fee line items from an order (by name).
     * Recalculates and saves the order.
     *
     * @param \WC_Order $order
     * @return bool True if at least one fee was removed
     */
    public static function remove_from_order(\WC_Order $order): bool
    {
        $removed = false;
        foreach ($order->get_items('fee') as $item_id => $fee_item) {
            if (self::is_interest_fee_item($fee_item)) {
                wc_delete_order_item($item_id);
                $removed = true;
            }
        }
        $order->calculate_shipping();
        $order->calculate_totals();
        $order->save();

        return $removed;
    }
}
