<?php

declare(strict_types=1);

use Netzkollektiv\EasyCredit\Compatibility\GermanizedInterestFeeCompatibility;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }
}

if (!class_exists('WC_Order_Item_Fee')) {
    class WC_Order_Item_Fee
    {
        private string $name = '';

        public function __construct(string $name = '')
        {
            $this->name = $name;
        }

        public function get_name(): string
        {
            return $this->name;
        }
    }
}

require_once dirname(__DIR__) . '/src/wc-easycredit/includes/InterestFeeHandler.php';
require_once dirname(__DIR__) . '/src/wc-easycredit/includes/Compatibility/GermanizedInterestFeeCompatibility.php';

final class GermanizedInterestFeeCompatibilityTest extends TestCase
{
    private GermanizedInterestFeeCompatibility $compatibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compatibility = new GermanizedInterestFeeCompatibility();
    }

    public function test_interest_fee_is_not_forced_into_split_tax(): void
    {
        $fee = (object) [
            'id' => 'easycredit-interest',
            'name' => 'Interest',
        ];

        $this->assertFalse($this->compatibility->doNotForceInterestFeeTax(true, $fee));
        $this->assertTrue($this->compatibility->skipInterestFeeSplitTax(false, $fee));
    }

    public function test_other_fees_keep_their_tax_calculation(): void
    {
        $fee = (object) [
            'id' => 'shipping-fee',
            'name' => 'Shipping',
        ];

        $this->assertTrue($this->compatibility->doNotForceInterestFeeTax(true, $fee));
        $this->assertFalse($this->compatibility->doNotForceInterestFeeTax(false, $fee));
        $this->assertFalse($this->compatibility->skipInterestFeeSplitTax(false, $fee));
    }

    public function test_interest_order_item_skips_split_tax(): void
    {
        $item = new WC_Order_Item_Fee('Interest');

        $this->assertTrue($this->compatibility->skipInterestFeeOrderItem(false, $item));
    }

    public function test_other_order_items_keep_split_tax(): void
    {
        $item = new WC_Order_Item_Fee('Shipping');

        $this->assertFalse($this->compatibility->skipInterestFeeOrderItem(false, $item));
    }
}
