<?php

/**
 * Plugin Name:     easyCredit for WooCommerce
 * Plugin URI:      https://www.easycredit-ratenkauf.de/
 * Description:     easyCredit - use the easiest pay later and installment purchase of Germany for your WooCommerce store now
 * Author:          NETZKOLLEKTIV
 * Author URI:      https://netzkollektiv.com
 * License:         MIT
 * License URI:     https://opensource.org/licenses/MIT
 * Text Domain:     wc-easycredit
 * Domain Path:     /languages
 * Version:         3.1.0
 * Requires Plugins: woocommerce
 * Requires at least: 4.4
 * Tested up to: 6.9
 * WC requires at least: 3.0.0
 * WC tested up to: 10.6.0
 *
 */

defined('ABSPATH') or exit;

define('WC_EASYCREDIT_VERSION', '3.1.0');
define('WC_EASYCREDIT_ID', 'easycredit');

use Netzkollektiv\EasyCredit\Plugin;

function wc_easycredit()
{
    static $plugin;

    if (!isset($plugin)) {

        require_once dirname(__FILE__) . '/vendor-prefixed/autoload.php';

        spl_autoload_register(function ($class) {
            $ds = DIRECTORY_SEPARATOR;
            $includesPath = plugin_dir_path(__FILE__) . 'includes';
            if (mb_strpos($class, 'Netzkollektiv\EasyCredit') === 0) {

                $file = str_replace(['_', 'Netzkollektiv\\EasyCredit\\', '\\'], $ds, $class) . '.php';
                if (file_exists($includesPath . $file)) {
                    require_once $includesPath . $file;
                    return;
                }
            }
        });

        $plugin = new Plugin(
            __FILE__
        );
    }

    return $plugin;
}
function is_woocommerce_plugin($plugin)
{
    $pattern = '/^woocommerce[\-\.0-9]*\/woocommerce.php$/';

    if (!preg_match($pattern, $plugin)) {
        return false;
    }

    return file_exists(WP_PLUGIN_DIR . '/' . $plugin);
}

function is_woocommerce_active()
{
    $activePlugins = apply_filters('active_plugins', get_option('active_plugins', []));
    $sitewidePlugins = get_site_option('active_sitewide_plugins', []);

    $allActivePlugins = array_merge($activePlugins, array_keys($sitewidePlugins));
    return !empty(array_filter($allActivePlugins, 'is_woocommerce_plugin', ARRAY_FILTER_USE_BOTH));
}

if (!is_woocommerce_active()) {
    return;
}

wc_easycredit()->maybe_run();
