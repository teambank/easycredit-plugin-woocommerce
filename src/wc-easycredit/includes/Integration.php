<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit;

use Teambank\EasyCreditApiV3 as ApiV3;

class Integration
{
    protected $plugin;

    protected $logger;

    protected $storage;

    protected $config;

    public function __construct(
        Plugin $plugin
    ) {
        $this->plugin = $plugin;
    }

    public function storage()
    {
        if ($this->storage == null) {
            $this->storage = new \Netzkollektiv\EasyCredit\Api\Storage(
                WC()->session,
                $this->logger()
            );
        }
        return $this->storage;
    }

    public function logger()
    {
        if ($this->logger == null) {
            $this->logger = new \Netzkollektiv\EasyCredit\Api\Logger($this->plugin);
        }
        return $this->logger;
    }

    public function config()
    {
        return ApiV3\Configuration::getDefaultConfiguration()
            ->setHost('https://ratenkauf.easycredit.de')
            ->setUsername($this->plugin->get_option('api_key'))
            ->setPassword($this->plugin->get_option('api_token'))
            ->setAccessToken($this->plugin->get_option('api_signature'));
    }

    public function checkout()
    {
        $logger = $this->logger();
        $config = $this->config();

        $client = new ApiV3\Client($logger);

        $webshopApi = new ApiV3\Service\WebshopApi(
            $client,
            $config
        );
        $transactionApi = new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
        $installmentPlanApi = new ApiV3\Service\InstallmentplanApi(
            $client,
            $config
        );

        return new ApiV3\Integration\Checkout(
            $webshopApi,
            $transactionApi,
            $installmentPlanApi,
            $this->storage(),
            new ApiV3\Integration\Util\AddressValidator(),
            new ApiV3\Integration\Util\PrefixConverter(),
            $this->logger()
        );
    }

    public function getCheckoutValidationMessage(?array $billing = null, ?array $shipping = null): string
    {
        if (!function_exists('WC')) {
            return '';
        }

        if (function_exists('wc_load_cart') && null === WC()->cart) {
            wc_load_cart();
        }

        if (!WC()->customer || !WC()->cart) {
            return '';
        }

        $previousAddresses = $this->snapshotCustomerAddresses();

        try {
            if ($billing !== null) {
                $this->applyCustomerAddress('billing', $billing);
            }
            if ($shipping !== null) {
                $this->applyCustomerAddress('shipping', $shipping);
            }

            $this->storage()->set('express', false);
            $quote = $this->quote_builder()->build();
            $this->checkout()->isAvailable($quote);

            return '';
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $this->restoreCustomerAddresses($previousAddresses);
        }
    }

    private function snapshotCustomerAddresses(): array
    {
        return [
            'billing' => WC()->customer->get_billing(),
            'shipping' => WC()->customer->get_shipping(),
        ];
    }

    private const BILLING_ADDRESS_FIELDS = [
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'country',
        'email',
        'phone',
    ];

    private const SHIPPING_ADDRESS_FIELDS = [
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'country',
    ];

    private function restoreCustomerAddresses(array $addresses): void
    {
        $this->setCustomerAddressFields('billing', $addresses['billing'], self::BILLING_ADDRESS_FIELDS);
        $this->setCustomerAddressFields('shipping', $addresses['shipping'], self::SHIPPING_ADDRESS_FIELDS);
    }

    private function applyCustomerAddress(string $type, array $address): void
    {
        $fields = $type === 'billing'
            ? self::BILLING_ADDRESS_FIELDS
            : self::SHIPPING_ADDRESS_FIELDS;

        $this->setCustomerAddressFields(
            $type,
            $this->normalizeCheckoutAddress($address),
            $fields
        );
    }

    private function setCustomerAddressFields(string $type, array $address, array $fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $address)) {
                WC()->customer->{"set_{$type}_{$field}"}($address[$field]);
            }
        }
    }

    private function normalizeCheckoutAddress(array $address): array
    {
        $aliases = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'address1' => 'address_1',
            'address2' => 'address_2',
            'postCode' => 'postcode',
            'postalCode' => 'postcode',
        ];

        $normalized = [];
        foreach ($address as $key => $value) {
            if (isset($aliases[$key])) {
                $key = $aliases[$key];
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    public function quote_builder()
    {
        return new \Netzkollektiv\EasyCredit\Api\QuoteBuilder(
            $this->plugin,
            $this->storage()
        );
    }

    public function order_builder()
    {
        return new \Netzkollektiv\EasyCredit\Api\OrderBuilder(
            $this->plugin,
            $this->storage()
        );
    }

    public function merchant_client()
    {
        $logger = $this->logger();
        $config = $this->config()
            ->setHost('https://partner.easycredit-ratenkauf.de');
        $client = new ApiV3\Client($logger);

        return new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
    }
}
