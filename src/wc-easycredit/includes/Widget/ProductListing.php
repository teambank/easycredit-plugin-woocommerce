<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Widget;

class ProductListing extends WidgetAbstract
{
    protected string $configKey = 'listing_widget_enabled';

    public function run()
    {
        parent::run();

        add_action('woocommerce_after_shop_loop_item', [$this, 'add_price_meta'], 6);
    }

    public function add_price_meta()
    {
        if (!$this->should_be_displayed()) {
            return;
        }
        global $product;
        echo '<div class="easycredit-placeholder"></div>';
        echo '<meta name="easycredit-amount" content="' . $product->get_price() . '">';
    }

    public function add_meta_tags($array)
    {
        parent::add_meta_tags($array);
        echo '<meta name="easycredit-widget-selector" content="' . $this->plugin->get_option('listing_widget_selector') . '">';
    }

    protected function should_be_displayed()
    {
        if (
            (!is_product_category() && !is_shop()) ||
            count($this->get_enabled_payment_types()) === 0
        ) {
            return false;
        }
        return true;
    }
}
