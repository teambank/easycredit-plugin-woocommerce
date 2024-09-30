<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Helper;

class TemporaryOrder
{
    private $tmp_order;

    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function get_order($paymentType = null)
    {
        add_action('woocommerce_checkout_create_order', [$this, 'abort_create_order']);
        add_filter('woocommerce_order_has_status', [$this, 'prevent_remove_items']);

        $postData = [];
        if (isset($_POST['post_data'])) {
            parse_str($_POST['post_data'], $postData);
        } else {
            $postData = $_POST;
        }
        
        if (isset($_REQUEST['easycredit']['paymentType'])) {
            $paymentType = $_REQUEST['easycredit']['paymentType'];
            $postData['payment_method'] = $this->plugin->get_method_by_payment_type($paymentType)->id;
        }

        $wc_checkout = \WC_Checkout::instance();
        $wc_checkout->create_order($postData);

        remove_filter('woocommerce_order_has_status', [$this, 'prevent_remove_items']);
        remove_action('woocommerce_checkout_create_order', [$this, 'abort_create_order'], 10);

        $order = $this->tmp_order;
        if ($order && isset($postData['ship_to_different_address'])) {
            $order->add_meta_data('ship_to_different_address', $postData['ship_to_different_address']);
        }
        return $order;
    }

    public function prevent_remove_items()
    {
        return false;
    }

    public function abort_create_order($order)
    {
        $this->tmp_order = $order;
        throw new \Exception(__CLASS__ . '_tmp_order');
    }
}
