<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Widget;

use Netzkollektiv\EasyCredit\Plugin;
use Netzkollektiv\EasyCredit\Gateway;

abstract class WidgetAbstract
{
    protected Plugin $plugin;
    protected array $paymentGateways;

    protected string $configKey;

    public function __construct(
        Plugin $plugin,
        array $paymentGateways
    ) {
        $this->plugin = $plugin;
        $this->paymentGateways = $paymentGateways;

        add_action('wp', [$this, 'run']);
    }

    public function run()
    {
        if (!$this->should_be_displayed()) {
            return;
        }

        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_ressources']);
    }

    public function enqueue_frontend_ressources($hook)
    {
        wp_enqueue_script(
            'wc_easycredit_js',
            $this->plugin->plugin_url . 'modules/frontend/build/index.js',
            ['easycredit-components-module'],
            '1.0'
        );
        wp_enqueue_style(
            'wc_easycredit_css',
            $this->plugin->plugin_url . 'modules/frontend/build/styles.css'
        );
    }

    public function add_meta_tags($array)
    {
        echo '<meta name="easycredit-api-key" content="' . $this->plugin->get_option('api_key') . '">';
        echo '<meta name="easycredit-payment-types" content="' . implode(',', $this->get_enabled_payment_types()) . '" initiator="' . get_class($this) . '">';
    }

    abstract protected function should_be_displayed();

    protected function get_enabled_payment_types()
    {
        $configKey = $this->configKey;
        return array_filter(array_map(function ($method) use ($configKey) {
            return $this->plugin->get_option($configKey) === 'yes' &&
                $method->get_option('enabled') === 'yes' ? $method->PAYMENT_TYPE : null;
        }, $this->paymentGateways));
    }
}
