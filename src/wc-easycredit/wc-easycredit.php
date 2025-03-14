<?php
/**
 * Plugin Name:     easyCredit for WooCommerce
 * Plugin URI:      https://www.easycredit-ratenkauf.de/
 * Description:     easyCredit - use the easiest pay later and installment purchase of Germany for your WooCommerce store now
 * Author:          NETZKOLLEKTIV
 * Author URI:      https://netzkollektiv.com
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     wc-easycredit
 * Domain Path:     /languages
 * Version:         3.0.1
 * Requires Plugins: woocommerce
 * Requires at least: 4.4
 * Tested up to: 6.5
 * WC requires at least: 3.0.0
 * WC tested up to: 9.0.2
 *
 */

defined('ABSPATH') or exit;

define('WC_EASYCREDIT_VERSION', '3.0.1');
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

function easyCreditCheckForWooCommerce($plugin)
{
    return preg_match('/^woocommerce[\-\.0-9]*\/woocommerce.php$/', $plugin);
}

$sitewidePlugins = is_array(get_site_option('active_sitewide_plugins')) ? get_site_option('active_sitewide_plugins') : [];
if (array_filter(
    array_merge(
        apply_filters('active_plugins', get_option('active_plugins')),
        array_keys($sitewidePlugins)
    ),
    'easyCreditCheckForWooCommerce',
    ARRAY_FILTER_USE_BOTH
)) {
    // Declare HPOS compatibility
    add_action('before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    });

    add_action('woocommerce_blocks_loaded', 'woocommerce_gateway_easycredit_woocommerce_block_support');

    function woocommerce_gateway_easycredit_woocommerce_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once dirname(__FILE__) . '/includes/Methods/Ratenkauf.php';
            require_once dirname(__FILE__) . '/includes/Methods/Rechnung.php';

            add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $container = Automattic\WooCommerce\Blocks\Package::container();

                $container->register(Netzkollektiv\EasyCredit\Methods\Ratenkauf::class, function () {
                    return new Netzkollektiv\EasyCredit\Methods\Ratenkauf(__FILE__);
                });
                $payment_method_registry->register(
                    $container->get(Netzkollektiv\EasyCredit\Methods\Ratenkauf::class)
                );

                $container->register(Netzkollektiv\EasyCredit\Methods\Rechnung::class, function () {
                    return new Netzkollektiv\EasyCredit\Methods\Rechnung(__FILE__);
                });
                $payment_method_registry->register(
                    $container->get(Netzkollektiv\EasyCredit\Methods\Rechnung::class)
                );
            }, 5);
        }
    }

    wc_easycredit()->maybe_run();
}
