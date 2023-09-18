<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_Gateway_Ratenkaufbyeasycredit_Widget
{
    protected $plugin;
    protected $plugin_url;
    protected $gateway;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->plugin_url = $plugin->plugin_url;
        $this->gateway = $this->plugin->get_gateway();

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
    abstract public function add_meta_tags($array);

    public function enqueue_frontend_ressources($hook)
    {
        wp_enqueue_script(
            'wc_ratenkaufbyeasycredit_js',
            $this->plugin_url . 'modules/frontend/build/index.js',
            ['easycredit-components-module'],
            '1.0'
        );
        wp_enqueue_style(
            'wc_ratenkaufbyeasycredit_css',
            $this->plugin_url . 'modules/frontend/build/styles.css'
        );
    }

    abstract protected function should_be_displayed();
}
