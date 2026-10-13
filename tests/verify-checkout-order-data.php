<?php

/**
 * Runs inside wp-env (wp eval-file). Checks order data easyCredit sends against
 * the WooCommerce version that is actually installed.
 */

use Netzkollektiv\EasyCredit\Admin\OrderManagement;
use Netzkollektiv\EasyCredit\Plugin;

final class EcCheck
{
    public static $failures = [];

    public static function assert($condition, $message): void
    {
        if (!$condition) {
            self::$failures[] = $message;
            echo "FAIL {$message}\n";
            return;
        }
        echo "ok   {$message}\n";
    }
}

function ec_assert($condition, $message)
{
    EcCheck::assert($condition, $message);
}

function ec_address($prefix)
{
    return [
        $prefix . '_first_name' => $prefix === 'billing' ? 'Rechnung' : 'Liefer',
        $prefix . '_last_name' => $prefix === 'billing' ? 'Kunde' : 'Anders',
        $prefix . '_company' => '',
        $prefix . '_address_1' => $prefix === 'billing' ? 'Rechnungsstrasse 1' : 'Lieferstrasse 9',
        $prefix . '_address_2' => '',
        $prefix . '_city' => $prefix === 'billing' ? 'Nuernberg' : 'Hamburg',
        $prefix . '_state' => '',
        $prefix . '_postcode' => $prefix === 'billing' ? '90402' : '20095',
        $prefix . '_country' => 'DE',
    ];
}

$createdOrders = [];
$userId = 0;

try {
    echo 'WooCommerce ' . WC()->version . ' / WordPress ' . get_bloginfo('version') . "\n";
    $hpos = class_exists(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)
        && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
    echo 'order storage: ' . ($hpos ? 'hpos' : 'posts') . "\n";

    wc_load_cart();
    WC()->cart->empty_cart();
    $product = wc_get_products([
        'limit' => 1,
        'status' => 'publish',
        'type' => 'simple',
        'return' => 'objects',
    ]);
    ec_assert(!empty($product), 'a simple product exists for the checkout simulation');
    $product = $product[0];
    WC()->cart->add_to_cart($product->get_id());
    ec_assert(WC()->cart->needs_shipping_address(), 'cart needs a shipping address, same as classic checkout');

    $billing = ec_address('billing');

    $_POST = array_merge($billing, [
        'billing_email' => 'same@example.com',
        'billing_phone' => '0911123456',
        'payment_method' => 'bacs',
        'woocommerce-process-checkout-nonce' => wp_create_nonce('woocommerce-process_checkout'),
    ]);
    $posted = WC()->checkout()->get_posted_data();
    ec_assert(empty($posted['ship_to_different_address']), 'unchecked ship-to-different-address stays false');
    ec_assert(
        ($posted['shipping_address_1'] ?? '') === 'Rechnungsstrasse 1' && ($posted['shipping_city'] ?? '') === 'Nuernberg',
        'classic checkout copies billing onto shipping when the checkbox is off'
    );

    $_POST['ship_to_different_address'] = '1';
    $_POST = array_merge($_POST, ec_address('shipping'));
    $postedDifferent = WC()->checkout()->get_posted_data();
    ec_assert(!empty($postedDifferent['ship_to_different_address']), 'checked ship-to-different-address stays true');
    ec_assert(
        ($postedDifferent['shipping_address_1'] ?? '') === 'Lieferstrasse 9',
        'classic checkout keeps the posted shipping street when the checkbox is on'
    );

    $userId = wp_insert_user([
        'user_login' => 'ec_compat_' . wp_generate_password(8, false),
        'user_pass' => wp_generate_password(),
        'user_email' => 'ec-compat-' . time() . '@example.com',
        'first_name' => 'Konto',
        'last_name' => 'Profil',
        'role' => 'customer',
    ]);
    ec_assert(!is_wp_error($userId) && $userId > 0, 'customer account can be created');

    $plugin = wc_easycredit();
    $orderBuilder = $plugin->integration()->order_builder();

    $makeOrder = function (array $billingAddress, array $shippingAddress, $meta = null) use ($userId, &$createdOrders) {
        $order = wc_create_order(['customer_id' => $userId]);
        $order->set_address($billingAddress, 'billing');
        $order->set_billing_email('rechnung@example.com');
        if ($shippingAddress) {
            $order->set_address($shippingAddress, 'shipping');
        }
        if ($meta !== null) {
            $order->update_meta_data('ship_to_different_address', $meta);
        }
        $order->set_payment_method('easycredit_ratenkauf');
        $order->save();
        $createdOrders[] = $order->get_id();
        return wc_get_order($order->get_id());
    };

    $billingOnly = [
        'first_name' => 'Rechnung',
        'last_name' => 'Kunde',
        'company' => '',
        'address_1' => 'Rechnungsstrasse 1',
        'address_2' => '',
        'city' => 'Nuernberg',
        'state' => '',
        'postcode' => '90402',
        'country' => 'DE',
        'email' => 'rechnung@example.com',
        'phone' => '0911123456',
    ];
    $shippingDifferent = [
        'first_name' => 'Liefer',
        'last_name' => 'Anders',
        'company' => '',
        'address_1' => 'Lieferstrasse 9',
        'address_2' => '',
        'city' => 'Hamburg',
        'state' => '',
        'postcode' => '20095',
        'country' => 'DE',
    ];

    $same = $makeOrder($billingOnly, $billingOnly);
    ec_assert($same->get_meta('ship_to_different_address') === '', 'identical addresses do not invent the legacy meta flag');
    $sameTx = $orderBuilder->build($same);
    ec_assert(
        $sameTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Rechnungsstrasse 1',
        'same billing and shipping still sends the billing street'
    );

    $different = $makeOrder($billingOnly, $shippingDifferent);
    $differentTx = $orderBuilder->build($different);
    $ordersAtBuild = wc_get_orders([
        'customer_id' => $userId,
        'limit' => -1,
        'return' => 'ids',
        'status' => array_keys(wc_get_order_statuses()),
        'type' => 'shop_order',
    ]);
    ec_assert(
        $differentTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Lieferstrasse 9'
            && $differentTx->getOrderDetails()->getShippingAddress()->getCity() === 'Hamburg',
        'a different shipping address is sent without the legacy meta flag'
    );
    ec_assert(
        $differentTx->getOrderDetails()->getInvoiceAddress()->getAddress() === 'Rechnungsstrasse 1',
        'invoice address stays the billing street'
    );
    ec_assert(
        $differentTx->getCustomer()->getFirstName() === 'Rechnung'
            && $differentTx->getCustomer()->getLastName() === 'Kunde',
        'logged-in customer name comes from the billing address, not the account profile'
    );

    $countryOnly = $makeOrder($billingOnly, [
        'first_name' => '',
        'last_name' => '',
        'address_1' => '',
        'city' => '',
        'postcode' => '',
        'country' => 'DE',
    ]);
    $countryTx = $orderBuilder->build($countryOnly);
    ec_assert(
        $countryTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Rechnungsstrasse 1',
        'a shipping country without a street still falls back to billing'
    );

    $legacy = $makeOrder($billingOnly, $shippingDifferent, '1');
    $legacyTx = $orderBuilder->build($legacy);
    ec_assert(
        $legacyTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Lieferstrasse 9',
        'legacy ship_to_different_address meta still selects the shipping address'
    );

    $legacyOff = $makeOrder($billingOnly, $billingOnly, 'no');
    $legacyOffTx = $orderBuilder->build($legacyOff);
    ec_assert(
        $legacyOffTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Rechnungsstrasse 1',
        'legacy meta "no" keeps the billing address when both addresses match'
    );

    ec_assert(
        (int) $differentTx->getCustomerRelationship()->getNumberOfOrders() === count($ordersAtBuild),
        'order count matches wc_get_orders for this customer (' . count($ordersAtBuild) . ', reported ' . (int) $differentTx->getCustomerRelationship()->getNumberOfOrders() . ')'
    );

    $legacyQuery = new WP_Query([
        'fields' => 'ids',
        'posts_per_page' => -1,
        'meta_key' => '_customer_user',
        'meta_value' => $userId,
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
    ]);
    $ordersForCustomer = wc_get_orders([
        'customer_id' => $userId,
        'limit' => -1,
        'return' => 'ids',
        'status' => array_keys(wc_get_order_statuses()),
        'type' => 'shop_order',
    ]);
    echo 'wp_query order count: ' . count($legacyQuery->posts) . ' / wc_get_orders: ' . count($ordersForCustomer) . "\n";
    if ($hpos) {
        ec_assert(
            count($legacyQuery->posts) < count($ordersForCustomer),
            'with HPOS the old post query under-counts, wc_get_orders does not'
        );
    }

    $guest = wc_create_order(['customer_id' => 0]);
    $guest->set_address($billingOnly, 'billing');
    $guest->set_address($shippingDifferent, 'shipping');
    $guest->set_payment_method('easycredit_ratenkauf');
    $guest->save();
    $createdOrders[] = $guest->get_id();
    $guestTx = $orderBuilder->build(wc_get_order($guest->get_id()));
    ec_assert(
        $guestTx->getCustomer()->getFirstName() === 'Rechnung'
            && $guestTx->getOrderDetails()->getShippingAddress()->getAddress() === 'Lieferstrasse 9'
            && (int) $guestTx->getCustomerRelationship()->getNumberOfOrders() === 0,
        'guests keep the billing name, the shipping street, and an order count of zero'
    );

    $metaOnly = $makeOrder($billingOnly, $billingOnly);
    $metaOnly->update_meta_data(Plugin::META_KEY_TRANSACTION_ID, 'tx-from-meta');
    $metaOnly->save();
    $orderManagement = new OrderManagement($plugin, $plugin->integration());
    $resolve = new ReflectionMethod(OrderManagement::class, 'resolve_transaction_id');
    $resolve->setAccessible(true);
    ec_assert(
        $resolve->invoke($orderManagement, $metaOnly) === 'tx-from-meta',
        'capture falls back to the easyCredit transaction meta when payment_complete did not set one'
    );

    $metaOnly->set_transaction_id('tx-from-order');
    $metaOnly->save();
    ec_assert(
        $resolve->invoke($orderManagement, wc_get_order($metaOnly->get_id())) === 'tx-from-order',
        'an existing WooCommerce transaction id stays in front of the meta value'
    );
} catch (Throwable $e) {
    EcCheck::$failures[] = $e->getMessage();
    echo 'ERROR ' . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
} finally {
    foreach ($createdOrders as $orderId) {
        $order = wc_get_order($orderId);
        if ($order) {
            $order->delete(true);
        }
    }
    if ($userId && !is_wp_error($userId)) {
        wp_delete_user($userId);
    }
}

if (!empty(EcCheck::$failures)) {
    echo count(EcCheck::$failures) . " failed\n";
    exit(1);
}

echo "all passed\n";
