<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

use Netzkollektiv\EasyCredit\Helper\MetaDataProvider;
use Teambank\RatenkaufByEasyCreditApiV3\Integration;
use Teambank\RatenkaufByEasyCreditApiV3\Model\ShoppingCartInformationItem;

class ItemBuilder
{
    protected $_product;
    protected $_item;

    public function getCategory()
    {
        $term_list = \wp_get_post_terms($this->_product->get_id(), 'product_cat', [
            'fields' => 'ids',
        ]);
        $term = \get_term(\current($term_list), 'product_cat');
        if ($term instanceof \WP_Term) {
            return $term->name;
        }
    }

    public function build(\WC_Order_Item_Product $item)
    {
        $this->_item = $item;
        $this->_product = $item->get_product();

        return new ShoppingCartInformationItem([
            'productName' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'price' => $item->get_subtotal(),
            'manufacturer' => '',
            'productCategory' => $this->getCategory(),
            'articleNumber' => [new \Teambank\RatenkaufByEasyCreditApiV3\Model\ArticleNumberItem([
                'numberType' => 'sku',
                'number' => $item->get_product_id(),
            ])],
        ]);
    }
}
