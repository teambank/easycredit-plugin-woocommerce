<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\InterestFeeHandler;

/**
 * WooCommerce German Market applies split tax to all cart fees via
 * woocommerce_cart_totals_get_fees_from_cart_taxes, ignoring WC's taxable flag.
 */
class GermanMarketInterestFeeCompatibility
{
    private static bool $registered = false;

    public function __construct()
    {
        // German Market registers fee tax hooks on init; register after that.
        add_action('woocommerce_init', [$this, 'register'], 20);
    }

    public function register(): void
    {
        if (self::$registered || !class_exists('WGM_Fee')) {
            return;
        }

        self::$registered = true;
        add_filter('woocommerce_de_calculate_gateway_fees_tax', [$this, 'disableInterestFeeTax'], 10, 2);
        add_filter('woocommerce_de_show_gateway_fees_tax', [$this, 'disableInterestFeeTax'], 10, 2);
        add_filter('german_market_return_before_recalc_taxes', [$this, 'skipInterestFeeOrderRecalc'], 10, 4);
    }

    /**
     * @param bool $calculate Whether German Market should calculate tax for this fee.
     * @param object $fee Cart fee line, cart totals wrapper, or WC_Order_Item_Fee.
     * @return bool
     */
    public function disableInterestFeeTax($calculate, $fee)
    {
        if (!$calculate || !InterestFeeHandler::is_interest_cart_fee_line($fee)) {
            return $calculate;
        }

        return false;
    }

    /**
     * German Market recalculates fee taxes on orders via woocommerce_order_item_fee_after_calculate_taxes.
     *
     * @param bool $return_value
     * @param string $order_item_type
     * @param \WC_Order_Item $order_item
     * @param array|string $calculate_tax_for
     * @return bool
     */
    public function skipInterestFeeOrderRecalc($return_value, $order_item_type, $order_item, $calculate_tax_for)
    {
        if ($return_value || $order_item_type !== 'fee') {
            return $return_value;
        }

        if ($order_item instanceof \WC_Order_Item_Fee && InterestFeeHandler::is_interest_fee_item($order_item)) {
            return true;
        }

        return $return_value;
    }
}
