<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\RatenkaufByEasyCreditApiV3\Integration\Util\PrefixConverter;
use Teambank\RatenkaufByEasyCreditApiV3\Model\InvoiceAddress;
use Teambank\RatenkaufByEasyCreditApiV3\Model\ShippingAddress;
use Teambank\RatenkaufByEasyCreditApiV3\Model\Transaction;

class QuoteBuilder
{
    protected $gateway;
    protected $storage;
    protected $systemBuilder;
    protected $addressBuilder;
    protected $customerBuilder;
    protected $itemBuilder;

    protected $quote;
    protected $customer;

    public function __construct(
        \WC_Easycredit_Gateway $gateway,
        \Netzkollektiv\EasyCredit\Api\Storage $storage
    ) {
        $this->gateway = $gateway;
        $this->storage = $storage;

        $this->systemBuilder = new SystemBuilder();
        $this->addressBuilder = new Quote\AddressBuilder();
        $this->customerBuilder = new Quote\CustomerBuilder(new PrefixConverter());
        $this->itemBuilder = new Quote\ItemBuilder();
    }

    public function getId()
    {
        return $this->quote->get_order_key();
    }

    public function getShippingMethod()
    {
        $shippingItem = \current($this->quote->get_items('shipping'));
        if ($shippingItem instanceof \WC_Order_Item_Shipping) {
            $shippingMethod = $shippingItem->get_name();
            if ($this->getIsClickAndCollect()) {
                $shippingMethod = '[Selbstabholung] ' . $shippingMethod;
            }
            return $shippingMethod;
        }
    }

    public function getIsClickAndCollect()
    {
        $shippingItem = \current($this->quote->get_items('shipping'));
        if ($shippingItem instanceof \WC_Order_Item_Shipping) {
            return $shippingItem->get_method_id() == $this->gateway->get_option('clickandcollect_shipping_method');
        }
    }

    public function getDuration(): ?string
    {
        return $this->storage->get('duration');
    }

    public function getGrandTotal()
    {
        return $this->quote->get_total();
    }

    public function isLoggedIn()
    {
        return ($this->customer !== false && $this->customer->get_id());
    }

    public function getInvoiceAddress()
    {
        $address = $this->quote->get_address('billing');
        if (!\array_filter($address) && $this->isLoggedIn()) {
            $address = $this->customer->get_billing();
        }

        return $this->addressBuilder
            ->setAddress(new InvoiceAddress())
            ->build($address);
    }

    public function getShippingAddress()
    {
        $_key = 'billing';
        if ($this->quote->get_meta('ship_to_different_address')) {
            $_key = 'shipping';
        }

        $address = $this->quote->get_address($_key);
        if (!\array_filter($address) && $this->isLoggedIn()) {
            $address = ($_key == 'billing') ? $this->customer->get_billing() : $this->customer->get_shipping();
        }

        return $this->addressBuilder
            ->setAddress(new ShippingAddress())
            ->build($address);
    }

    public function getCustomer() {
        return $this->customerBuilder->build(
            $this->quote,
            $this->customer
        );
    }

    public function getItems()
    {
        return $this->_getItems(
            $this->quote->get_items()
        );
    }

    public function getSystem()
    {
        return $this->systemBuilder->build();
    }

    public function getOrderCount()
    {
        if (!$this->isLoggedIn()) {
            return 0;
        }

        $query = new \WP_Query();
        $query->query([
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'meta_value' => $this->customer->get_id(),
            'post_type' => \wc_get_order_types(),
            'post_status' => \array_keys(\wc_get_order_statuses()),
        ]);
        return $query->found_posts;
    }

    public function build(\WC_Order $order): Transaction
    {
        $this->quote = $order;
        $this->customer = new \WC_Customer($order->get_user_id());

        $transaction =  new Transaction([
            'financingTerm' => $this->getDuration(),
            'orderDetails' => new \Teambank\RatenkaufByEasyCreditApiV3\Model\OrderDetails([
                'orderValue' => $this->getGrandTotal(),
                'orderId' => $this->getId(),
                'numberOfProductsInShoppingCart' => \count($this->getItems()),
                'invoiceAddress' => $this->isExpress() ? null : $this->getInvoiceAddress(),
                'shippingAddress' => $this->isExpress() ? null : $this->getShippingAddress(),
                'shoppingCartInformation' => $this->getItems(),
            ]),
            'shopsystem' => $this->getSystem(),
            'customer' => $this->getCustomer(),
            'customerRelationship' => new \Teambank\RatenkaufByEasyCreditApiV3\Model\CustomerRelationship([
                'customerSince' => ($this->customer->get_date_created() instanceof \WC_DateTime) ? $this->customer->get_date_created()->format('Y-m-d') : null,
                'orderDoneWithLogin' => $this->isLoggedIn(),
                'numberOfOrders' => $this->getOrderCount(),
                'logisticsServiceProvider' => $this->getShippingMethod(),
            ]),
            'redirectLinks' => $this->getRedirectLinks(),
        ]);
        $transaction = apply_filters('easycredit_quotebuilder_filter_transaction', $transaction);

        return $transaction;
    }

    protected function _getItems(array $items): array
    {
        $_items = [];
        foreach ($items as $item) {
            $quoteItem = $this->itemBuilder->build($item);
            if ($quoteItem->getPrice() <= 0) {
                continue;
            }
            $_items[] = $quoteItem;
        }

        return $_items;
    }

    protected function getCancelUrl($order)
    {
        $uri = $order->get_cancel_endpoint();
        if ($order->get_id() > 0) {
            return \add_query_arg(
                [
                    'cancel_order' => 'true',
                    'order' => $order->get_order_key(),
                    'order_id' => $order->get_id(),
                    '_wpnonce' => \wp_create_nonce('woocommerce-cancel_order'),
                ],
                $uri
            );
        }
        return \esc_url_raw($uri);
    }

    protected function getRedirectLinks()
    {
        return new \Teambank\RatenkaufByEasyCreditApiV3\Model\RedirectLinks([
            'urlSuccess' => $this->gateway->plugin->get_review_page_uri(),
            'urlCancellation' => $this->getCancelUrl($this->quote),
            'urlDenial' => $this->getCancelUrl($this->quote)
        ]);
    }

    protected function isExpress()
    {
        return $this->storage->get('express');
    }
}
