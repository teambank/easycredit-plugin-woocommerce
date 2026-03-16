<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

use Teambank\EasyCreditApiV3\Model\ShoppingCartInformationItem;
use Teambank\EasyCreditApiV3\Model\ArticleNumberItem;

class ItemBuilder
{
    protected $_product;
    protected $_item;

    public function getCategory()
    {
        if (!$this->_product) {
            return null;
        }

        $term_list = \wp_get_post_terms($this->_product->get_id(), 'product_cat', [
            'fields' => 'ids',
        ]);

        if ($term_list && !\is_wp_error($term_list) && \is_array($term_list) && !empty($term_list)) {
            $term_id = \current($term_list);
            if ($term_id) {
                $term = \get_term($term_id, 'product_cat');
                if ($term instanceof \WP_Term) {
                    return $term->name;
                }
            }
        }

        return null;
    }

    public function build($item)
    {
        // Handle both WC_Order_Item_Product (order items) and cart item arrays
        if ($item instanceof \WC_Order_Item_Product) {
            return $this->buildFromOrderItem($item);
        } elseif (\is_array($item) && isset($item['data']) && $item['data'] instanceof \WC_Product) {
            return $this->buildFromCartItem($item);
        }
        return null;
    }

    protected function buildFromOrderItem(\WC_Order_Item_Product $item)
    {
        $this->_item = $item;
        $this->_product = $item->get_product();

        return new ShoppingCartInformationItem([
            'productName' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_subtotal(),
            'manufacturer' => '',
            'productCategory' => $this->getCategory(),
            'articleNumber' => [new ArticleNumberItem([
                'numberType' => 'sku',
                'number' => $item->get_product_id(),
            ])],
        ]);
    }

    protected function buildFromCartItem(array $cart_item)
    {
        $this->_item = $cart_item;
        $this->_product = $cart_item['data'];

        // Calculate line subtotal (quantity * price)
        $quantity = $cart_item['quantity'];
        $line_subtotal = $cart_item['line_subtotal'] ?? ($this->_product->get_price() * $quantity);

        return new ShoppingCartInformationItem([
            'productName' => $this->_product->get_name(),
            'quantity' => $quantity,
            'price' => $line_subtotal,
            'manufacturer' => '',
            'productCategory' => $this->getCategory(),
            'articleNumber' => [new ArticleNumberItem([
                'numberType' => 'sku',
                'number' => $this->_product->get_id(),
            ])],
        ]);
    }
}
