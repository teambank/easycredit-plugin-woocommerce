<?php

namespace Netzkollektiv\EasyCredit\Methods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

class AbstractMethod extends AbstractPaymentMethodType
{
    protected $plugin_file = null;

    protected $method_settings = null;

    protected $integration = null;

    protected $place_order_button_label;

    public function __construct($plugin_file, $integration = null)
    {
        $this->plugin_file = $plugin_file;
        $this->integration = $integration;
    }

    public function initialize()
    {
        $this->settings = get_option('woocommerce_easycredit_settings', []);
        $this->method_settings = get_option('woocommerce_' . $this->name . '_settings', []);
    }

    public function is_active()
    {
        if (isset($this->method_settings['enabled'])) {
            return $this->method_settings['enabled'] === 'yes';
        }
        return true;
    }

    public function get_payment_method_script_handles()
    {
        $this->register_script_handles();

        return ['wc-easycredit-blocks'];
    }

    private function register_script_handles()
    {
        $dir = 'modules/checkout/build';

        $asset_file = plugin_dir_path($this->plugin_file) . $dir . '/index.asset.php';
        $asset_data = require $asset_file;
        $dependencies = $asset_data['dependencies'] ?? [];
        $version = $asset_data['version'] ?? '1.0';

        wp_register_script(
            'wc-easycredit-blocks',
            plugin_dir_url($this->plugin_file) . $dir . '/index.js',
            $dependencies,
            $version,
            true
        );
        wp_set_script_translations(
            'wc-easycredit-blocks',
            'wc-easycredit'
        );
    }

    public function get_payment_method_data()
    {
        $method_data = [
            'id'          => $this->name,
            'title'       => $this->method_settings['title'] ?? '',
            'description' => $this->method_settings['description'] ?? '',
            'supports'    => ['products'],
            'enabled'     => $this->is_active(),
            'apiKey'      => $this->settings['api_key'],
            'expressUrl'  => get_site_url(null, 'easycredit/express'),
            'placeOrderButtonLabel' => $this->place_order_button_label
        ];

        $payment_plan = $this->integration ? $this->integration->storage()->get('summary') : null;

        // Only add easycredit-selection as required feature if payment plan exists and this is the currently selected payment method
        if ($payment_plan && WC()->session->get('chosen_payment_method') === $this->name) {
            $method_data['supports'][] = 'easycredit_selection';
            $method_data['paymentPlan'] = $payment_plan;
        }

        return $method_data;
    }
}
