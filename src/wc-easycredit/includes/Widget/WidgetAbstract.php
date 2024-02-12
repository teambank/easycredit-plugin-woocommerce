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
    protected Gateway\Ratenkauf $payment;

    public function __construct(
        Plugin $plugin,
        Gateway\Ratenkauf $payment
    ) {
        $this->plugin = $plugin;
        $this->payment = $payment;

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

    abstract protected function should_be_displayed();
}