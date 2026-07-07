<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Netzkollektiv\EasyCredit\Integration;

/**
 * Validates checkout addresses for blocks checkout via the REST API.
 *
 * Naming in this plugin: PascalCase class names, snake_case method names
 * (no camelCase for functions/methods).
 */
class CheckoutValidation
{
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

    protected Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    public function get_message(?array $billing = null, ?array $shipping = null): string
    {
        return $this->get_result($billing, $shipping)['message'];
    }

    /**
     * @return array{message: string, invalidated: bool}
     */
    public function get_result(?array $billing = null, ?array $shipping = null): array
    {
        if (!function_exists('WC')) {
            return ['message' => '', 'invalidated' => false];
        }

        if (function_exists('wc_load_cart') && null === WC()->cart) {
            wc_load_cart();
        } elseif (null === WC()->session && method_exists(WC(), 'initialize_session')) {
            WC()->initialize_session();
        }

        if (!WC()->customer || !WC()->cart || !WC()->session) {
            return ['message' => '', 'invalidated' => false];
        }

        $previous_addresses = $this->snapshot_customer_addresses();
        $checkout = $this->integration->checkout();

        try {
            if ($billing !== null) {
                $this->apply_customer_address('billing', $billing);
            }
            if ($shipping !== null) {
                $this->apply_customer_address('shipping', $shipping);
            }

            $this->integration->storage()->set('express', false);
            $quote = $this->integration->quote_builder()->build();

            try {
                $checkout->isAvailable($quote);
            } catch (\Exception $e) {
                $checkout->clear();

                return [
                    'message' => $e->getMessage(),
                    'invalidated' => true,
                ];
            }

            return ['message' => '', 'invalidated' => false];
        } finally {
            $this->restore_customer_addresses($previous_addresses);
        }
    }

    private function snapshot_customer_addresses(): array
    {
        return [
            'billing' => WC()->customer->get_billing(),
            'shipping' => WC()->customer->get_shipping(),
        ];
    }

    private function restore_customer_addresses(array $addresses): void
    {
        $this->set_customer_address_fields('billing', $addresses['billing'], self::BILLING_ADDRESS_FIELDS);
        $this->set_customer_address_fields('shipping', $addresses['shipping'], self::SHIPPING_ADDRESS_FIELDS);
    }

    private function apply_customer_address(string $type, array $address): void
    {
        $fields = $type === 'billing'
            ? self::BILLING_ADDRESS_FIELDS
            : self::SHIPPING_ADDRESS_FIELDS;

        $this->set_customer_address_fields(
            $type,
            $this->normalize_checkout_address($address),
            $fields
        );
    }

    private function set_customer_address_fields(string $type, array $address, array $fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $address)) {
                WC()->customer->{"set_{$type}_{$field}"}($address[$field]);
            }
        }
    }

    private function normalize_checkout_address(array $address): array
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
}
