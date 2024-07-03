<?php

namespace Netzkollektiv\EasyCredit\Methods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

class AbstractMethod extends AbstractPaymentMethodType
{
    protected $plugin_file = null;

    protected $method_settings = null;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
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

        $dependencies = [];
        $version = '1.0';

        require plugin_dir_path($this->plugin_file) . $dir . '/index.asset.php';

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
        return [
            'id'          => $this->name,
            'title'       => $this->method_settings['title'] ?? '',
            'description' => $this->method_settings['description'] ?? '',
            'supports'    => ['products'],
            'enabled'     => $this->is_active(),
            'apiKey'      => $this->settings['api_key'],
            'expressUrl'  => get_site_url(null, 'easycredit/express')
        ];
    }
}
