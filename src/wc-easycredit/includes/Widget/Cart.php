<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Widget;

class Cart extends WidgetAbstract
{
    protected string $configKey = 'cart_widget_enabled';

    public function add_meta_tags($array)
    {
        $cartTotal = WC()->cart->get_total('raw');
        if ($cartTotal > 0) {
            parent::add_meta_tags($array);

            echo '<meta name="easycredit-widget-selector" content="' . $this->plugin->get_option('cart_widget_selector') . '">';
            echo '<meta name="easycredit-amount" content="' . $cartTotal . '">';
            echo '<meta name="easycredit-api-key" content="' . $this->plugin->get_option('api_key') . '">';
        }
    }

    protected function should_be_displayed()
    {
        if (
            !is_cart()
            || count($this->get_enabled_payment_types()) === 0
            || trim($this->plugin->get_option('api_key')) == ''
            || WC()->cart->get_total('raw') === 0
        ) {
            return false;
        }
        return true;
    }
}
