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

    public function getShippingAddress()
    {
        $_key = 'billing';
        if ($this->order->get_meta('ship_to_different_address')) {
            $_key = 'shipping';
        }

        $address = $this->order->get_address($_key);

        // Fallback to stored customer data if order doesn't have address
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

        // Otherwise redirect to checkout - customer may want to change the order
        return \esc_url_raw(\wc_get_checkout_url());
    }
}
