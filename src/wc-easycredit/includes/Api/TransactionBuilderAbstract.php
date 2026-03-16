<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\EasyCreditApiV3\Model\Transaction;

use Netzkollektiv\EasyCredit\Plugin;

abstract class TransactionBuilderAbstract
{
    protected $plugin;
    protected $storage;
    protected $systemBuilder;
    protected $addressBuilder;
    protected $customerBuilder;
    protected $itemBuilder;

    protected $customer;

    public function __construct(
        Plugin $plugin,
        Storage $storage
    ) {
        $this->plugin = $plugin;
        $this->storage = $storage;

        $this->systemBuilder = new SystemBuilder();
        $this->addressBuilder = new Quote\AddressBuilder();
        $this->customerBuilder = new Quote\CustomerBuilder();
        $this->itemBuilder = new Quote\ItemBuilder();
    }

    abstract public function getId();

    abstract public function getPaymentType();

    abstract public function getShippingMethod();

    abstract public function getIsClickAndCollect();

    abstract public function getGrandTotal();

    abstract public function getInvoiceAddress();

    abstract public function getShippingAddress();

    abstract public function getCustomer();

    abstract public function getItems();

    abstract protected function getCancelUrl();

    public function getFinancingTerm(): ?string
    {
        return (string) intval($this->storage->get('numberOfInstallments'));
    }

    public function isLoggedIn()
    {
        return ($this->customer !== false && $this->customer->get_id());
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

    protected function _getItems(array $items): array
    {
        $_items = [];
        foreach ($items as $item) {
            // Handle both cart items (arrays) and order items (objects)
            $quoteItem = $this->itemBuilder->build($item);
            if ($quoteItem && $quoteItem->getPrice() <= 0) {
                continue;
            }
            if ($quoteItem) {
                $_items[] = $quoteItem;
            }
        }
        return $_items;
    }

    protected function getRedirectLinks()
    {
        return new \Teambank\EasyCreditApiV3\Model\RedirectLinks([
            'urlSuccess' => get_site_url(null, 'easycredit/return'),
            'urlCancellation' => $this->getCancelUrl(),
            'urlDenial' => $this->getCancelUrl()
        ]);
    }

    protected function isExpress()
    {
        return $this->storage->get('express');
    }

    protected function buildTransaction(): Transaction
    {
        $transaction = new Transaction([
            'financingTerm' => $this->getFinancingTerm(),
            'paymentType' => $this->getPaymentType(),
            'paymentSwitchPossible' => count($this->plugin->get_enabled_payment_methods()) > 1,
            'orderDetails' => new \Teambank\EasyCreditApiV3\Model\OrderDetails([
                'orderValue' => $this->getGrandTotal(),
                'orderId' => $this->getId(),
                'numberOfProductsInShoppingCart' => \count($this->getItems()),
                'invoiceAddress' => $this->isExpress() ? null : $this->getInvoiceAddress(),
                'shippingAddress' => $this->isExpress() ? null : $this->getShippingAddress(),
                'shoppingCartInformation' => $this->getItems(),
            ]),
            'shopsystem' => $this->getSystem(),
            'customer' => $this->getCustomer(),
            'customerRelationship' => new \Teambank\EasyCreditApiV3\Model\CustomerRelationship([
                'customerSince' => ($this->customer->get_date_created() instanceof \WC_DateTime) ? $this->customer->get_date_created()->format('Y-m-d') : null,
                'orderDoneWithLogin' => $this->isLoggedIn(),
                'numberOfOrders' => $this->getOrderCount(),
                'logisticsServiceProvider' => $this->getShippingMethod(),
            ]),
            'redirectLinks' => $this->getRedirectLinks(),
        ]);

        return $transaction;
    }
}
