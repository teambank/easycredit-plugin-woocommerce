<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Easycredit_Widget_Cart extends WC_Easycredit_Widget
{
    public function add_meta_tags($array)
    {
        $cartTotal = WC()->cart->get_total('raw');
        if ($cartTotal > 0) {
            echo '<meta name="easycredit-widget-selector" content="' . $this->gateway->get_option('cart_widget_selector') . '">';
            echo '<meta name="easycredit-widget-price" content="' . $cartTotal . '">';
            echo '<meta name="easycredit-api-key" content="' . $this->gateway->get_option('api_key') . '">';
        }
    }

    protected function should_be_displayed()
    {
        /* @var \WP_Post $post */
        if (!is_cart()
            || $this->gateway->get_option('cart_widget_enabled') != 'yes'
            || trim($this->gateway->get_option('api_key')) == ''
            || WC()->cart->get_total('raw') === 0
        ) {
            return false;
        }
        return true;
    }
}
