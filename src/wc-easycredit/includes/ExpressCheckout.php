<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\OrderController as LegacyOrderController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

class ExpressCheckout
{
    protected $plugin;

    protected $integration;

    protected $payment;

    public function __construct(
        Plugin $plugin,
        Integration $integration, 
        Gateway\Ratenkauf $payment
    ) {
        $this->plugin = $plugin;
        $this->integration = $integration;
        $this->payment = $payment;

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
            add_action('woocommerce_single_product_summary', [$this, 'add_button_at_product'], 30);
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

    protected function create_express_checkout_order($transaction)
    {
        $updateAddress = function ($address, $type = 'billing') {
            $fields = [
                'first_name' => $address->getFirstName(),
                'last_name' => $address->getLastName(),
                'address_1' => $address->getAddress(),
                'address_2' => '',
                'city' => $address->getCity(),
                'state' => '',
                'postcode' => $address->getZip(),
                'country' => $address->getCountry(),
            ];
            array_walk($fields, function ($value, $field) use ($type) {
                WC()->customer->{"set_{$type}_{$field}"}($value);
            });
        };
        $updateAddress($transaction->getTransaction()->getOrderDetails()->getInvoiceAddress());
        $updateAddress($transaction->getTransaction()->getOrderDetails()->getShippingAddress(), 'shipping');

        $contact = $transaction->getTransaction()->getCustomer()->getContact();
        WC()->customer->set_billing_phone($contact->getMobilePhoneNumber());
        WC()->customer->set_billing_email($contact->getEmail());
        WC()->customer->save();

        $order_data = [];
        foreach (['billing', 'shipping'] as $prefix) {
            array_walk(WC()->customer->get_data()[$prefix], function ($value, $field) use (&$order_data, $prefix) {
                $order_data[$prefix . '_' . $field] = $value;
            });
        }

        $order = new \WC_Order();
        $order->set_created_via('easycredit-express-checkout');
        $order->add_order_note(__('Created via express checkout', 'wc-easycredit'));
        $order->set_payment_method($this->payment->id);

        if (class_exists(OrderController::class)) {
            $orderController = new OrderController();
            $orderController->update_order_from_cart($order);
        } elseif (class_exists(LegacyOrderController::class)) {
            $orderController = new LegacyOrderController();
            $orderController->update_order_from_cart($order);
        }

        $this->integration->storage()
            ->set('order_id', $order->get_id())
            ->set('express', false);
    }

    public function add_button_at_product()
    {
        $post = get_post();

        $product = new \WC_Product($post->ID);

        if ($product->is_in_stock() && $product->get_price() > 199 && $product->get_price() <= 10000) {
            echo '<easycredit-express-button 
                webshop-id="' . $this->plugin->get_option('api_key') . '"
                amount="' . $product->get_price() . '"
            ></easycredit-express-button>';
        }
    }

    public function add_button_in_cart()
    {
        echo '<easycredit-express-button 
            webshop-id="' . $this->plugin->get_option('api_key') . '"
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
            $this->payment->get_option('express_checkout_detail_enabled') != 'yes' ||
            trim($this->plugin->get_option('api_key')) == ''
        ) {
            return false;
        }
        return true;
    }

    protected function should_be_displayed_in_cart()
    {
        if ($this->payment->get_option('express_checkout_cart_enabled') != 'yes' ||
            trim($this->plugin->get_option('api_key')) == '' ||
            WC()->cart->get_total('raw') === 0 ||
            !is_cart()
        ) {
            return false;
        }
        return true;
    }
}
