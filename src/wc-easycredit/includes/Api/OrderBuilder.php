<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\EasyCreditApiV3\Model\InvoiceAddress;
use Teambank\EasyCreditApiV3\Model\ShippingAddress;
use Teambank\EasyCreditApiV3\Model\Transaction;

class OrderBuilder extends TransactionBuilderAbstract
{
    protected $order;

    public function getId()
    {
        return (string) $this->order->get_id();
    }

    public function getPaymentType()
    {
        $paymentMethod = $this->order->get_payment_method();
        $paymentType = $this->plugin->get_payment_type_by_method($paymentMethod);
        return $paymentType ? $paymentType . '_PAYMENT' : null;
    }

    public function getShippingMethod()
    {
        $shippingItem = \current($this->order->get_items('shipping'));
        if ($shippingItem instanceof \WC_Order_Item_Shipping) {
            $shippingMethod = $shippingItem->get_name();
            if ($this->getIsClickAndCollect()) {
                $shippingMethod = '[Selbstabholung] ' . $shippingMethod;
            }
            return $shippingMethod;
        }
        return null;
    }

    public function getIsClickAndCollect()
    {
        $shippingItem = \current($this->order->get_items('shipping'));
        if ($shippingItem instanceof \WC_Order_Item_Shipping) {
            return $shippingItem->get_method_id() == $this->plugin->get_option('clickandcollect_shipping_method');
        }
        return false;
    }

    public function getGrandTotal()
    {
        return $this->order->get_total();
    }

    public function getInvoiceAddress()
    {
        $address = $this->order->get_address('billing');

        // Fallback to stored customer data if order doesn't have address
        if (empty(\array_filter($address)) && $this->isLoggedIn()) {
            $address = $this->customer->get_billing();
        }

        return $this->addressBuilder
            ->setAddress(new InvoiceAddress(null))
            ->build($address);
    }

    /**
     * Classic checkout (WooCommerce 3 through 11) copies billing onto the shipping
     * fields when the customer does not ship elsewhere, and never stores
     * ship_to_different_address on the order. Block checkout writes both addresses
     * directly. A stored flag is still honored for checkouts that did save it.
     */
    public function getShippingAddress()
    {
        $shipping = $this->order->get_address('shipping');
        $useShipping = $this->legacyShipToDifferentAddress() || $this->shippingAddressDiffers($shipping);
        $address = $useShipping ? $shipping : $this->order->get_address('billing');

        // Fallback to stored customer data if order doesn't have address
        if (empty(\array_filter($address)) && $this->isLoggedIn()) {
            if ($useShipping) {
                $address = $this->customer->get_shipping();
                if (empty(\array_filter($address))) {
                    $address = $this->customer->get_billing();
                }
            } else {
                $address = $this->customer->get_billing();
            }
        }

        return $this->addressBuilder
            ->setAddress(new ShippingAddress(null))
            ->build($address);
    }

    protected function legacyShipToDifferentAddress(): bool
    {
        $flag = $this->order->get_meta('ship_to_different_address');
        if ($flag === '' || $flag === null || is_array($flag)) {
            return false;
        }
        if (is_bool($flag)) {
            return $flag;
        }

        return function_exists('wc_string_to_bool')
            ? wc_string_to_bool((string) $flag)
            : in_array((string) $flag, ['1', 'yes', 'true'], true);
    }

    protected function shippingAddressDiffers(array $shipping): bool
    {
        // Country alone is not a destination: older checkouts leave the base country
        // on an otherwise empty shipping address when nothing is shipped separately.
        if (!$this->addressHasDestination($shipping)) {
            return false;
        }

        $billing = $this->order->get_address('billing');
        foreach (['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'] as $field) {
            if ($this->addressField($shipping, $field) !== $this->addressField($billing, $field)) {
                return true;
            }
        }

        return false;
    }

    protected function addressHasDestination(array $address): bool
    {
        foreach (['address_1', 'city', 'postcode'] as $field) {
            if ($this->addressField($address, $field) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function addressField(array $address, string $field): string
    {
        return isset($address[$field]) ? trim((string) $address[$field]) : '';
    }

    public function getCustomer()
    {
        return $this->customerBuilder->build(
            $this->order,
            $this->customer
        );
    }

    public function getItems()
    {
        return $this->_getItems(
            $this->order->get_items()
        );
    }


    public function build(\WC_Order $order): Transaction
    {
        $this->order = $order;
        $this->customer = new \WC_Customer($order->get_user_id());

        $transaction = $this->buildTransaction();
        $transaction = apply_filters('easycredit_orderbuilder_filter_transaction', $transaction);

        return $transaction;
    }

    protected function getCancelUrl()
    {
        global $wp;

        // If we're on order-pay page, redirect back to order payment page
        if (isset($wp->query_vars['order-pay'])) {
            return \esc_url_raw($this->order->get_checkout_payment_url());
        }

        return \esc_url_raw(\wc_get_checkout_url());
    }
}
