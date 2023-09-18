<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Ratenkaufbyeasycredit_Widget_Product extends WC_Gateway_Ratenkaufbyeasycredit_Widget
{
    public function add_meta_tags($array)
    {
        $post = get_post();

        $product = new WC_Product($post->ID);
        if ($product->get_id()) {
            echo '<meta name="easycredit-widget-selector" content="' . $this->gateway->get_option('widget_selector') . '">';
            echo '<meta name="easycredit-widget-price" content="' . $product->get_price() . '">';
            echo '<meta name="easycredit-api-key" content="' . $this->gateway->get_option('api_key') . '">';
        }
    }

    protected function should_be_displayed()
    {
        $post = get_post();

        if (!isset($post->ID)) {
            return false;
        }

        if ($post->post_type != 'product'
            || !$post->ID
            || !is_product()
            || $this->gateway->get_option('widget_enabled') != 'yes'
        ) {
            return false;
        }
        return true;
    }
}
