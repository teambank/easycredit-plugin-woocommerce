<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'label' => __('Enable easyCredit-Ratenkauf Payment', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'default' => 'yes',
    ],

    'display_settings' => [
        'title' => __('Display settings', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'title' => [
        'title' => __('Title', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
        'description' => __('This controls the title for the payment method the customer sees during checkout.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
    ],

    'instructions' => [
        'title' => __('Instructions', 'woocommerce'),
        'type' => 'textarea',
        'description' => __('Instructions that will be added to the thank you page and emails.', 'woocommerce'),
        'default' => '',
        'desc_tip' => true,
    ],

    'api_details' => [
        'title' => __('API credentials', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],
    'api_key' => [
        'title' => __('API Key', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
    ],

    'api_token' => [
        'title' => __('API Token', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
    ],

    'api_signature' => [
        'title' => __('API Signature (if activated in partner portal, optional)', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
        'description' => __('The API signature secures the data transfer against data manipulation by third parties. You can activate the API signature in the easyCredit-Ratenkauf merchant portal. ', 'woocommerce-gateway-ratenkaufbyeasycredit'),
    ],

    'api_verify_credentials' => [
        'title' => '',
        'type' => 'button',
        'default' => __('Verify Credentials', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'class' => 'button-primary',
    ],

    'debug' => [
        'title' => __('Debug Logging', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'label' => __('Enable Debug Logging', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'default' => 'no',
    ],

    'marketing_intro' => [
        'type' => 'marketingintro',
    ],

    'marketing_components_express_checkout' => [
        'title' => __('Express Checkout', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'express_checkout_detail_enabled' => [
        'title' => __('Show expresss checkout button at product detail page', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'description' => __('Let customers initiate easyCredit-Ratenkauf directly from the product detail page', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => 'yes',
    ],

    'express_checkout_cart_enabled' => [
        'title' => __('Show expresss checkout button in cart', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'description' => __('Let customers initiate easyCredit-Ratenkauf directly from the cart page', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => 'yes',
    ],

    'marketing_components_widget' => [
        'title' => __('Marketing', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'widget_enabled' => [
        'title' => __('Show widget next to product price', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'description' => __('Specifies if easyCredit-Ratenkauf will be advertised in product detail view', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => 'yes',
    ],

    'widget_selector' => [
        'title' => __('CSS Selector to include widget at product page', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
        'description' => __('If the widget is not shown in the right place, please enter a selector which matches your theme.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => '.product .summary .price',
    ],

    'cart_widget_enabled' => [
        'title' => __('Show widget in Cart', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'description' => __('Specifies if easyCredit-Ratenkauf will be advertised in cart view', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => 'yes',
    ],

    'cart_widget_selector' => [
        'title' => __('CSS Selector to include widget at the cart page', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'text',
        'description' => __('If the widget is not shown in the right place, please enter a selector which matches your theme.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'default' => '.wc-proceed-to-checkout',
    ],

    'marketing_components_modal' => [
        'title' => __('Marketing - Modal', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'modal_enabled' => [
        'title' => __('Activate modal', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'label' => __('Show modal automatically when visiting the online shop for the first time', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no'
    ],

    'modal_delay' => [
        'title' => __('Delay (in seconds)', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'description' => __('Here you can specify in seconds the delay after which the modal is displayed to the customer when the page is loaded (for example "10" for 10 seconds delay).', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'type' => 'number',
        'default' => '10',
    ],

    'modal_snooze_for' => [
        'title' => __('Reactivate after (in seconds)', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'description' => __('Here you can specify in seconds the time after which the modal is displayed again to the customer (when the page is loaded) after he has actively closed the modal (for example "3600" for 1 hour).', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'desc_tip' => true,
        'type' => 'number',
        'default' => '10',
    ],

    'modal_src' => [
        'title' => __('Use your own image', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'hidden',
        'default' => '',
    ],

    'marketing_components_card' => [
        'title' => __('Marketing - Card', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'card_enabled' => [
        'title' => __('Activate card', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'label' => __('Show card within the product list (category)', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],

    'card_search_enabled' => [
        'title' => __('Activate card (search)', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'label' => __('Show card within search results', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],

    'card_position' => [
        'title' => __('Position in product list', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'number',
        'default' => '1',
    ],

    'card_src' => [
        'title' => __('Use your own image', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'hidden',
        'default' => '',
    ],

    'marketing_components_flashbox' => [
        'title' => __('Marketing - Flashbox', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'flashbox_enabled' => [
        'title' => __('Activate flashbox', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'label' => __('Show flashbox at the bottom of the screen', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],

    'flashbox_src' => [
        'title' => __('Use your own image', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'hidden',
        'default' => '',
    ],

    'marketing_components_bar' => [
        'title' => __('Marketing - Bar', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'bar_enabled' => [
        'title' => __('Activate bar', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'label' => __('Show bar at the top of the screen', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],

    'transactions' => [
        'title' => __('Order Management', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'mark_shipped' => [
        'title' => __('Confirm shipping automatically?', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],
    'mark_shipped_status' => [
        'title' => __('Order status to confirm shipping', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'select',
        'default' => 'wc-completed',
        'options' => wc_get_order_statuses(),
    ],

    'mark_refunded' => [
        'title' => __('Refund automatically?', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'checkbox',
        'default' => 'no',
    ],
    'mark_refunded_status' => [
        'title' => __('Order status to refund', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'select',
        'default' => 'wc-refunded',
        'options' => wc_get_order_statuses(),
    ],

    'clickandcollect_intro' => [
        'type' => 'clickandcollectintro',
    ],

    'marketing_components_clickandcollect' => [
        'title' => __('Click & Collect', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'title',
    ],

    'clickandcollect_shipping_method' => [
        'title' => __('Shipping method', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        'type' => 'select',
        'default' => '',
        'options' => [
            '' => '',
        ],
    ],
];
