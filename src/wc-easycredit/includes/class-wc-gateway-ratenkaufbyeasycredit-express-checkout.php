<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Ratenkaufbyeasycredit_Express_Checkout
{
    protected $plugin;

    protected $plugin_url;

    protected $gateway;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->plugin_url = $plugin->plugin_url;
        $this->gateway = $this->plugin->get_gateway();

        add_action('init', [$this, 'run']);
        add_action('wp', [$this, 'init_buttons']);
    }

    public function run()
    {
        if ($this->is_express_action()) {
            add_filter('woocommerce_add_to_cart_product_id', [$this, 'clear_cart']);
            add_filter('woocommerce_add_to_cart_redirect', [$this, 'handle_express_redirect']);
        }
    }

    public function init_buttons()
    {
        if ($this->should_be_displayed_at_product()) {
            add_action('woocommerce_after_add_to_cart_button', [$this, 'add_button_at_product'], 30);
        }
        if ($this->should_be_displayed_in_cart()) {
            add_action('woocommerce_proceed_to_checkout', [$this, 'add_button_in_cart']);
        }
    }

    public function is_express_action()
    {
        return isset($_REQUEST['easycredit-express']);
    }

    public function clear_cart($product_id)
    {
        WC()->cart->empty_cart();
        return $product_id;
    }

    public function handle_express_redirect($url)
    {
        if (wp_redirect(esc_url_raw(get_home_url(null, '/easycredit/express')))) {
          exit;
        }
    }

    public function add_button_at_product()
    {
        $post = get_post();

        $product = wc_get_product($post->ID);
        $amount = $product->get_price();
        if ($product->is_type('variable')) {
            $amount = 1;  // default display has no selection, do not show button implicitly
        }

        if ($product->is_in_stock() || $product->is_type('variable')) {
            echo '<easycredit-express-button 
                webshop-id="' . $this->gateway->get_option('api_key') . '"
                amount="' . $amount . '"
            ></easycredit-express-button>';
        }
    }

    public function add_button_in_cart()
    {
        echo '<easycredit-express-button 
            webshop-id="' . $this->gateway->get_option('api_key') . '"
            amount="' . WC()->cart->get_total('raw') . '"
            full-width
            data-url="'.get_site_url(null, 'easycredit/express').'"
        ></easycredit-express-button>';
    }

    protected function should_be_displayed_at_product()
    {
        $post = get_post();

        if (!isset($post->ID) ||
            $post->post_type != 'product' ||
            !is_product() ||
            $this->gateway->get_option('express_checkout_detail_enabled') != 'yes' ||
            trim($this->gateway->get_option('api_key')) == ''
        ) {
            return false;
        }
        return true;
    }

    protected function should_be_displayed_in_cart()
    {
        if ($this->gateway->get_option('express_checkout_cart_enabled') != 'yes' ||
            trim($this->gateway->get_option('api_key')) == '' ||
            WC()->cart->get_total('raw') === 0 ||
            !is_cart()
        ) {
            return false;
        }
        return true;
    }
}
