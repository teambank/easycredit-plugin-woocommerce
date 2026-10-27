<?php

declare(strict_types=1);

use Netzkollektiv\EasyCredit\Api\CheckoutValidation;
use Netzkollektiv\EasyCredit\Integration;
use PHPUnit\Framework\TestCase;

if (!class_exists('WC_Data_Exception')) {
    class WC_Data_Exception extends Exception
    {
        /**
         * @param mixed $code
         * @param mixed $message
         * @param mixed $http_status_code
         * @param mixed $data
         */
        public function __construct($code = '', $message = '', $http_status_code = 400, $data = [])
        {
            parent::__construct((string) $message);
        }
    }
}

if (!defined('EASYCREDIT_TEST_OWNS_WC')) {
    define('EASYCREDIT_TEST_OWNS_WC', !function_exists('WC'));
}

if (EASYCREDIT_TEST_OWNS_WC) {
    function WC()
    {
        return $GLOBALS['easycredit_checkout_validation_wc'];
    }
}

require_once dirname(__DIR__) . '/src/wc-easycredit/includes/Integration.php';
require_once dirname(__DIR__) . '/src/wc-easycredit/includes/Api/CheckoutValidation.php';

final class CheckoutValidationCustomerDouble
{
    public array $billing;

    public array $shipping;

    public function __construct(array $billing, array $shipping)
    {
        $this->billing = $billing;
        $this->shipping = $shipping;
    }

    public function get_billing(): array
    {
        return $this->billing;
    }

    public function get_shipping(): array
    {
        return $this->shipping;
    }

    public function set_billing_email($value): void
    {
        $email = (string) $value;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new WC_Data_Exception(
                'customer_invalid_billing_email',
                'Invalid billing email address'
            );
        }

        $this->billing['email'] = $email;
    }

    public function __call(string $name, array $arguments): void
    {
        if (!preg_match('/^set_(billing|shipping)_(.+)$/', $name, $matches)) {
            throw new BadMethodCallException($name);
        }

        $this->{$matches[1]}[$matches[2]] = $arguments[0] ?? '';
    }
}

final class CheckoutValidationWcDouble
{
    public CheckoutValidationCustomerDouble $customer;

    public object $cart;

    public object $session;

    public function __construct(CheckoutValidationCustomerDouble $customer)
    {
        $this->customer = $customer;
        $this->cart = (object) [];
        $this->session = (object) [];
    }
}

final class CheckoutValidationCheckoutDouble
{
    public int $isAvailableCalls = 0;

    public function isAvailable($quote): void
    {
        $this->isAvailableCalls++;
    }

    public function clear(): void
    {
    }
}

final class CheckoutValidationStorageDouble
{
    public int $setCalls = 0;

    public function set($key, $value): void
    {
        $this->setCalls++;
    }
}

final class CheckoutValidationQuoteBuilderDouble
{
    public function build(): object
    {
        return (object) [];
    }
}

final class CheckoutValidationTest extends TestCase
{
    private CheckoutValidationCustomerDouble $customer;

    private CheckoutValidationCheckoutDouble $checkout;

    private CheckoutValidationStorageDouble $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (!EASYCREDIT_TEST_OWNS_WC) {
            $this->markTestSkipped('WordPress WC() is already loaded.');
        }
    }

    public function test_invalid_billing_email_is_a_validation_result_and_restores_the_customer(): void
    {
        $validation = $this->validationWithCustomer([
            'email' => 'saved@example.com',
            'phone' => '111',
            'first_name' => 'Saved',
        ], [
            'first_name' => 'Saved',
        ]);

        $result = $validation->get_result(
            [
                'first_name' => 'Erika',
                'email' => 'max@web',
                'phone' => '222',
            ],
            [
                'first_name' => 'Erika',
            ]
        );

        $this->assertSame('Invalid billing email address', $result['message']);
        $this->assertTrue($result['invalidated']);
        $this->assertSame(0, $this->checkout->isAvailableCalls);
        $this->assertSame(0, $this->storage->setCalls);
        $this->assertSame('saved@example.com', $this->customer->billing['email']);
        $this->assertSame('111', $this->customer->billing['phone']);
        $this->assertSame('Saved', $this->customer->billing['first_name']);
        $this->assertSame('Saved', $this->customer->shipping['first_name']);
    }

    public function test_invalid_stored_email_does_not_escape_while_restoring_later_fields(): void
    {
        $validation = $this->validationWithCustomer([
            'email' => 'not-an-email',
            'phone' => '111',
        ], []);

        $result = $validation->get_result([
            'email' => 'still-not-an-email',
            'phone' => '222',
        ], null);

        $this->assertTrue($result['invalidated']);
        $this->assertSame('not-an-email', $this->customer->billing['email']);
        $this->assertSame('111', $this->customer->billing['phone']);
    }

    public function test_valid_billing_email_still_reaches_checkout_and_restores_the_customer(): void
    {
        $validation = $this->validationWithCustomer([
            'email' => 'saved@example.com',
            'postcode' => '80331',
        ], []);

        $result = $validation->get_result([
            'email' => 'buyer@example.com',
            'postcode' => 10115,
        ], null);

        $this->assertSame('', $result['message']);
        $this->assertFalse($result['invalidated']);
        $this->assertSame(1, $this->checkout->isAvailableCalls);
        $this->assertSame('saved@example.com', $this->customer->billing['email']);
        $this->assertSame('80331', $this->customer->billing['postcode']);
    }

    private function validationWithCustomer(array $billing, array $shipping): CheckoutValidation
    {
        $this->customer = new CheckoutValidationCustomerDouble($billing, $shipping);
        $GLOBALS['easycredit_checkout_validation_wc'] = new CheckoutValidationWcDouble($this->customer);
        $this->checkout = new CheckoutValidationCheckoutDouble();
        $this->storage = new CheckoutValidationStorageDouble();
        $quoteBuilder = new CheckoutValidationQuoteBuilderDouble();

        $integration = new class($this->checkout, $this->storage, $quoteBuilder) extends Integration {
            public CheckoutValidationCheckoutDouble $checkoutDouble;

            public CheckoutValidationStorageDouble $storageDouble;

            public CheckoutValidationQuoteBuilderDouble $quoteBuilderDouble;

            public function __construct(
                CheckoutValidationCheckoutDouble $checkout,
                CheckoutValidationStorageDouble $storage,
                CheckoutValidationQuoteBuilderDouble $quoteBuilder
            ) {
                $this->checkoutDouble = $checkout;
                $this->storageDouble = $storage;
                $this->quoteBuilderDouble = $quoteBuilder;
            }

            public function checkout()
            {
                return $this->checkoutDouble;
            }

            public function storage()
            {
                return $this->storageDouble;
            }

            public function quote_builder()
            {
                return $this->quoteBuilderDouble;
            }
        };

        return new CheckoutValidation($integration);
    }
}
