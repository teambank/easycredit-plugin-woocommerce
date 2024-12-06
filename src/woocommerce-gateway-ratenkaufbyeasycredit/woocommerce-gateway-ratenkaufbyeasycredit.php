<?php
/**
 * Plugin Name:     easyCredit-Ratenkauf for WooCommerce
 * Plugin URI:      https://www.easycredit-ratenkauf.de/
 * Description:     easyCredit-Ratenkauf - use the easiest installment purchase of Germany for your WooCommerce store now
 * Author:          NETZKOLLEKTIV
 * Author URI:      https://netzkollektiv.com
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     woocommerce-gateway-ratenkaufbyeasycredit
 * Domain Path:     /languages
 * Version:         2.1.10
 * Requires at least: 4.4
 * Tested up to: 6.7
 * WC requires at least: 3.0.0
 * WC tested up to: 9.4.2
 *
 * @package         Woocommerce_Gateway_Ratenkaufbyeasycredit
 */

defined('ABSPATH') or exit;

define('WC_RATENKAUFBYEASYCREDIT_VERSION', '2.1.10');
define('WC_RATENKAUFBYEASYCREDIT_ID', 'ratenkaufbyeasycredit');

function wc_ratenkaufbyeasycredit()
{
    static $plugin;

    if (!isset($plugin)) {
        require_once(dirname(__FILE__) . '/includes/class-wc-gateway-ratenkaufbyeasycredit-plugin.php');

        $plugin = new WC_Gateway_Ratenkaufbyeasycredit_Plugin(
            __FILE__
        );
    }

    return $plugin;
}

function ratenkaufByEasyCreditCheckForWooCommerce($plugin)
{
    return preg_match('/^woocommerce[\-\.0-9]*\/woocommerce.php$/', $plugin);
}

$sitewidePlugins = is_array(get_site_option('active_sitewide_plugins')) ? get_site_option('active_sitewide_plugins') : [];
if (array_filter(
    array_merge(
        apply_filters('active_plugins', get_option('active_plugins')),
        array_keys($sitewidePlugins)
    ),
    'ratenkaufByEasyCreditCheckForWooCommerce',
    ARRAY_FILTER_USE_BOTH
)) {
    
    // Declare HPOS compatibility
    add_action('before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
        }
    });

    add_action('woocommerce_blocks_loaded', 'woocommerce_gateway_easycredit_woocommerce_block_support');

    function woocommerce_gateway_easycredit_woocommerce_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once dirname(__FILE__) . '/includes/class-wc-gateway-ratenkaufbyeasycredit-payment-method.php';

            add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $container = Automattic\WooCommerce\Blocks\Package::container();
                    $container->register(WC_Gateway_Ratenkaufbyeasycredit_Payment_Method::class, function () {
                        return new WC_Gateway_Ratenkaufbyeasycredit_Payment_Method(__FILE__);
                    });
                    $payment_method_registry->register(
                        $container->get(WC_Gateway_Ratenkaufbyeasycredit_Payment_Method::class)
                    );
            }, 5);
        }
    }

    wc_ratenkaufbyeasycredit()->maybe_run();
}
