<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Widget;

class Product extends WidgetAbstract
{
    protected string $configKey = 'widget_enabled';

    public function add_meta_tags($array)
    {
        $post = get_post();

        $product = new \WC_Product($post->ID);
        if ($product->get_id()) {
            parent::add_meta_tags($array);
            echo '<meta name="easycredit-widget-selector" content="' . $this->plugin->get_option('widget_selector') . '">';
            echo '<meta name="easycredit-amount" content="' . wc_get_price_including_tax($product) . '">';
        }
    }

    protected function should_be_displayed()
    {
        /* @var \WP_Post $post */
        $post = get_post();

        if (!isset($post->ID)) {
            return false;
        }

        if (
            $post->post_type != 'product'
            || !$post->ID
            || !is_product()
            || count($this->get_enabled_payment_types()) === 0
        ) {
            return false;
        }
        return true;
    }
}
