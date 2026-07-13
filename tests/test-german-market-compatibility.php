<?php

declare(strict_types=1);

use Netzkollektiv\EasyCredit\Compatibility\GermanMarketInterestFeeCompatibility;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
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
require_once dirname(__DIR__) . '/src/wc-easycredit/includes/Compatibility/GermanMarketInterestFeeCompatibility.php';

final class GermanMarketCompatibilityTest extends TestCase
{
    private GermanMarketInterestFeeCompatibility $compatibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compatibility = new GermanMarketInterestFeeCompatibility();
    }

    public function test_disable_interest_fee_tax_for_interest_cart_fee_line(): void
    {
        $fee = (object) [
            'id' => 'easycredit-interest',
            'name' => 'Interest',
        ];

        $result = $this->compatibility->disableInterestFeeTax(true, $fee);

        $this->assertFalse($result);
    }

    public function test_keep_tax_calculation_for_non_interest_fee_line(): void
    {
        $fee = (object) [
            'id' => 'shipping-fee',
            'name' => 'Shipping',
        ];

        $result = $this->compatibility->disableInterestFeeTax(true, $fee);

        $this->assertTrue($result);
    }

    public function test_skip_order_fee_tax_recalculation_for_interest_fee_item(): void
    {
        $orderItem = new WC_Order_Item_Fee('Interest');

        $result = $this->compatibility->skipInterestFeeOrderRecalc(false, 'fee', $orderItem, []);

        $this->assertTrue($result);
    }

    public function test_do_not_skip_recalculation_for_non_interest_fee_item(): void
    {
        $orderItem = new WC_Order_Item_Fee('Shipping');

        $result = $this->compatibility->skipInterestFeeOrderRecalc(false, 'fee', $orderItem, []);

        $this->assertFalse($result);
    }
}
