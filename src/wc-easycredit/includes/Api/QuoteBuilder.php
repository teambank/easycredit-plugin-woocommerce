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

class QuoteBuilder extends TransactionBuilderAbstract
{
    protected $cart;

    public function getId()
    {
        return (string) $this->cart->get_cart_hash();
    }

    public function getPaymentType()
    {
        $paymentMethod = WC()->session->get('chosen_payment_method');
        return $this->plugin->get_payment_type_by_method($paymentMethod) . '_PAYMENT';
    }

    public function getShippingMethod()
    {
        // For WC_Cart, get shipping method from session
        if (WC()->session) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            if (!empty($chosen_shipping_methods)) {
                $shipping_method_id = \current($chosen_shipping_methods);
                $packages = WC()->shipping()->get_packages();
                if (!empty($packages)) {
                    $available_methods = \current($packages)['rates'] ?? [];
                    if (isset($available_methods[$shipping_method_id])) {
                        $shippingMethod = $available_methods[$shipping_method_id]->get_label();
                        if ($this->getIsClickAndCollect()) {
                            $shippingMethod = '[Selbstabholung] ' . $shippingMethod;
                        }
                        return $shippingMethod;
                    }
                }
            }
        }
        return null;
    }

    public function getIsClickAndCollect()
    {
        // For WC_Cart, get shipping method from session
        if (WC()->session) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            if (!empty($chosen_shipping_methods)) {
                $shipping_method_id = \current($chosen_shipping_methods);
                // Extract method ID (format is usually "method_id:instance_id")
                $method_parts = \explode(':', $shipping_method_id);
                $method_id = $method_parts[0];
                return $method_id == $this->plugin->get_option('clickandcollect_shipping_method');
            }
        }
        return false;
    }

    public function getGrandTotal()
    {
        return $this->cart->get_total(null);
    }

    public function getInvoiceAddress()
    {
        // For WC_Cart, get address from WC()->customer which has the current checkout values
        $address = [];
        if (WC()->customer) {
            $address = WC()->customer->get_billing();
        }

        // Fallback to stored customer data if WC()->customer doesn't have address
        if (empty(\array_filter($address)) && $this->isLoggedIn()) {
            $address = $this->customer->get_billing();
        }

        return $this->addressBuilder
            ->setAddress(new InvoiceAddress(null))
            ->build($address);
    }

    public function getShippingAddress()
    {
        $postData = [];
        if (isset($_POST['post_data'])) {
            \parse_str($_POST['post_data'], $postData);
        } else {
            $postData = $_POST;
        }

        $ship_to_different_address = ! empty($postData['ship_to_different_address']) && ! \wc_ship_to_billing_address_only();

        $_key = 'billing';
        if ($ship_to_different_address) {
            $_key = 'shipping';
        }

        // Get address from WC()->customer which has the current checkout values
        $address = [];
        if (WC()->customer) {
            $address = ($_key == 'billing') ? WC()->customer->get_billing() : WC()->customer->get_shipping();
        }

        // Fallback to stored customer data if WC()->customer doesn't have address
        if (empty(\array_filter($address)) && $this->isLoggedIn()) {
            $address = ($_key == 'billing') ? $this->customer->get_billing() : $this->customer->get_shipping();
        }

        return $this->addressBuilder
            ->setAddress(new ShippingAddress(null))
            ->build($address);
    }

    public function getCustomer()
    {
        return $this->customerBuilder->build(
            $this->cart,
            $this->customer
        );
    }

    public function getItems()
    {
        return $this->_getItems(
            $this->cart->get_cart_contents()
        );
    }


    public function build(): Transaction
    {
        $this->cart = WC()->cart;
        $this->customer = $this->cart->get_customer();

        $transaction = $this->buildTransaction();
        $transaction = apply_filters('easycredit_quotebuilder_filter_transaction', $transaction);

        return $transaction;
    }

    protected function getCancelUrl()
    {
        // For WC_Cart, return cart URL as cancellation URL
        return \esc_url_raw(\wc_get_checkout_url());
    }
}
