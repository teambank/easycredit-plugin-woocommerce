<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\InterestFeeHandler;

/**
 * Germanized split-tax applies to every fee, including ones marked not taxable.
 * The interest is the bank's charge and must not be taxed again.
 */
class GermanizedInterestFeeCompatibility
{
    public function __construct()
    {
        add_filter('woocommerce_gzd_force_fee_tax_calculation', [$this, 'doNotForceInterestFeeTax'], 10, 2);
        add_filter('woocommerce_gzd_skip_fee_split_tax_calculation', [$this, 'skipInterestFeeSplitTax'], 10, 2);
        add_filter('woocommerce_gzd_skip_order_item_split_tax_calculation', [$this, 'skipInterestFeeOrderItem'], 10, 2);
    }

    /**
     * Germanized forces tax onto non-taxable fees unless this returns false.
     *
     * @param bool $force
     * @param object $fee
     */
    public function doNotForceInterestFeeTax($force, $fee): bool
    {
        if (!InterestFeeHandler::is_interest_cart_fee_line($fee)) {
            return (bool) $force;
        }

        return false;
    }

    /**
     * @param bool $skip
     * @param object $fee
     */
    public function skipInterestFeeSplitTax($skip, $fee): bool
    {
        if ($skip || !InterestFeeHandler::is_interest_cart_fee_line($fee)) {
            return (bool) $skip;
        }

        return true;
    }

    /**
     * @param bool $skip
     * @param object $item
     */
    public function skipInterestFeeOrderItem($skip, $item): bool
    {
        if ($skip || !InterestFeeHandler::is_interest_fee_item($item)) {
            return (bool) $skip;
        }

        return true;
    }
}
