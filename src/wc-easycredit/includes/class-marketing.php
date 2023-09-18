<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!defined('ABSPATH')) {
    exit;
}

class WC_Easycredit_Marketing
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

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_ressources']);
        add_action('wp_footer', [$this, 'add_component_tags_footer']);
        add_action('woocommerce_shop_loop', [$this, 'add_component_tags_shop_loop']);
        add_filter('loop_start', [$this, 'add_component_tags_loop_start']);
        add_filter('body_class', [$this, 'add_body_class']);
    }

    public function add_component_tags_footer($array)
    {
        if ( $this->gateway->get_option('modal_enabled') == 'yes' ) {
            echo '<easycredit-box-modal src="' . $this->gateway->get_option('modal_src') . '" is-open="false" delay="' . $this->gateway->get_option('modal_delay') * 1000 . '" snooze-for="' . $this->gateway->get_option('modal_snooze_for') . '"></easycredit-box-modal>';
        }

        if ( $this->gateway->get_option('flashbox_enabled') == 'yes' ) {
            echo '<easycredit-box-flash is-open="false" src="' . $this->gateway->get_option('flashbox_src') . '"></easycredit-box-flash>';
        }

        if ( $this->gateway->get_option('bar_enabled') == 'yes' ) {
            echo '<easycredit-box-top></easycredit-box-top>';
        }
    }

    public function add_component_tags_shop_loop($array)
    {
        if (
            is_search() ||
            did_action('add_component_tags_shop_loop') ||
            did_action('add_component_tags_loop_start')
        ) {
            return;
        }

        if ( $this->gateway->get_option('card_enabled') == 'yes' ) {
            echo '<easycredit-box-listing class="easycredit-box-listing-adjusted" src="' . $this->gateway->get_option('card_src') . '" position="' . $this->gateway->get_option('card_position') . '"></easycredit-box-listing>';
        }

        do_action( 'add_component_tags_shop_loop' );
    }

    public function add_component_tags_loop_start($array)
    {
        if (
            !is_search() ||
            did_action('add_component_tags_shop_loop')
        ) {
            return;
        }

        if ( $this->gateway->get_option('card_search_enabled') == 'yes' ) {
            echo '<easycredit-box-listing class="easycredit-box-listing-adjusted" src="' . $this->gateway->get_option('card_src') . '" position="' . $this->gateway->get_option('card_position') . '"></easycredit-box-listing>';
        }
    }

    public function add_body_class($classes)
    {
        if ( $this->gateway->get_option('bar_enabled') == 'yes' ) {
            $classes[] = 'easycredit-box-top';
        }

        return $classes;
    }

    public function enqueue_frontend_ressources($hook)
    {
        wp_enqueue_script(
            'wc_easycredit_js',
            $this->plugin_url . 'modules/frontend/build/index.js',
            ['easycredit-components-module'],
            '1.0'
        );
        wp_enqueue_style(
            'wc_easycredit_css',
            $this->plugin_url . 'modules/frontend/build/styles.css'
        );
    }

    protected function should_be_displayed()
    {
        $post = get_post();

        if (!isset($post->ID)) {
            return false;
        }

        return true;
    }
}
