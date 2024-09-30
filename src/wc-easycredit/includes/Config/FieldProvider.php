<?php

namespace Netzkollektiv\EasyCredit\Config;

class FieldProvider
{
    public function get_fields_by_section($section)
    {
        return array_filter($this->get_fields(), function ($item) use ($section) {
            if (!isset($item['sections'])) {
                $item['sections'] = ['easycredit'];
            }

            if (in_array($section, $item['sections'])) {
                return true;
            }
        });
    }

    public function get_fields()
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'wc-easycredit'),
                'type' => 'checkbox',
                'label' => __('Enable payment method', 'wc-easycredit'),
                'default' => 'yes',
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']

            ],

            'display_settings' => [
                'title' => __('Display settings', 'wc-easycredit'),
                'type' => 'title',
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'title' => [
                'title' => __('Title', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-easycredit'),
                'desc_tip' => true,
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'subtitle' => [
                'title' => __('Subtitle', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('This controls the subtitle for the payment method the customer sees during checkout.', 'wc-easycredit'),
                'desc_tip' => true,
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'instructions' => [
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'api_details' => [
                'title' => __('API credentials', 'wc-easycredit'),
                'type' => 'title',
            ],
            'api_key' => [
                'title' => __('API Key', 'wc-easycredit'),
                'type' => 'text',
            ],

            'api_token' => [
                'title' => __('API Token', 'wc-easycredit'),
                'type' => 'text',
            ],

            'api_signature' => [
                'title' => __('API Signature (if activated in partner portal, optional)', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('The API signature secures the data transfer against data manipulation by third parties. You can activate the API signature in the easycredit_ratenkauf merchant portal. ', 'wc-easycredit'),
            ],

            'api_verify_credentials' => [
                'title' => '',
                'type' => 'button',
                'default' => __('Verify Credentials', 'wc-easycredit'),
                'class' => 'button-primary',
            ],

            'debug' => [
                'title' => __('Debug Logging', 'wc-easycredit'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Logging', 'wc-easycredit'),
                'default' => 'no',
            ],

            'marketing_intro' => [
                'type' => 'marketingintro',
            ],

            'marketing_components_express_checkout' => [
                'title' => __('Express Checkout', 'wc-easycredit'),
                'type' => 'title',
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'express_checkout_detail_enabled' => [
                'title' => __('Show express checkout button at product detail page', 'wc-easycredit'),
                'type' => 'checkbox',
                'description' => __('Let customers initiate easycredit_ratenkauf directly from the product detail page', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'yes',
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'express_checkout_cart_enabled' => [
                'title' => __('Show express checkout button in cart', 'wc-easycredit'),
                'type' => 'checkbox',
                'description' => __('Let customers initiate easycredit_ratenkauf directly from the cart page', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'yes',
                'sections' => ['easycredit_ratenkauf', 'easycredit_rechnung']
            ],

            'marketing_components_widget' => [
                'title' => __('Marketing', 'wc-easycredit'),
                'type' => 'title',
            ],

            'widget_enabled' => [
                'title' => __('Show widget next to product price', 'wc-easycredit'),
                'type' => 'checkbox',
                'description' => __('Specifies if easycredit_ratenkauf will be advertised in product detail view', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'yes',
            ],

            'widget_selector' => [
                'title' => __('CSS Selector to include widget at product page', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('If the widget is not shown in the right place, please enter a selector which matches your theme.', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => '.product .summary .price',
            ],

            'listing_widget_enabled' => [
                'title' => __('Show widget in product listing', 'wc-easycredit'),
                'type' => 'checkbox',
                'description' => __('Specifies if easycredit_ratenkauf will be advertised in product listing view', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'yes',
            ],

            'listing_widget_selector' => [
                'title' => __('CSS Selector to include widget in product listing page', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('If the widget is not shown in the right place, please enter a selector which matches your theme.', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'ul.products li.product .easycredit-placeholder easycredit-widget[display-type=minimal]',
            ],


            'cart_widget_enabled' => [
                'title' => __('Show widget in Cart', 'wc-easycredit'),
                'type' => 'checkbox',
                'description' => __('Specifies if easycredit_ratenkauf will be advertised in cart view', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => 'yes',
            ],

            'cart_widget_selector' => [
                'title' => __('CSS Selector to include widget at the cart page', 'wc-easycredit'),
                'type' => 'text',
                'description' => __('If the widget is not shown in the right place, please enter a selector which matches your theme.', 'wc-easycredit'),
                'desc_tip' => true,
                'default' => '.wc-proceed-to-checkout',
            ],

            'marketing_components_modal' => [
                'title' => __('Marketing - Modal', 'wc-easycredit'),
                'type' => 'title',
            ],

            'modal_enabled' => [
                'title' => __('Activate modal', 'wc-easycredit'),
                'label' => __('Show modal automatically when visiting the online shop for the first time', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
                'sections' => ['easycredit_ratenkauf']
            ],

            'modal_delay' => [
                'title' => __('Delay (in seconds)', 'wc-easycredit'),
                'description' => __('Here you can specify in seconds the delay after which the modal is displayed to the customer when the page is loaded (for example "10" for 10 seconds delay).', 'wc-easycredit'),
                'desc_tip' => true,
                'type' => 'number',
                'default' => '10',
                'sections' => ['easycredit_ratenkauf']
            ],

            'modal_snooze_for' => [
                'title' => __('Reactivate after (in seconds)', 'wc-easycredit'),
                'description' => __('Here you can specify in seconds the time after which the modal is displayed again to the customer (when the page is loaded) after he has actively closed the modal (for example "3600" for 1 hour).', 'wc-easycredit'),
                'desc_tip' => true,
                'type' => 'number',
                'default' => '10',
                'sections' => ['easycredit_ratenkauf']
            ],

            'modal_src' => [
                'title' => __('Use your own image', 'wc-easycredit'),
                'type' => 'hidden',
                'default' => '',
                'sections' => ['easycredit_ratenkauf']
            ],

            'marketing_components_card' => [
                'title' => __('Marketing - Card', 'wc-easycredit'),
                'type' => 'title',
                'sections' => ['easycredit_ratenkauf']
            ],

            'card_enabled' => [
                'title' => __('Activate card', 'wc-easycredit'),
                'label' => __('Show card within the product list (category)', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
                'sections' => ['easycredit_ratenkauf']
            ],

            'card_search_enabled' => [
                'title' => __('Activate card (search)', 'wc-easycredit'),
                'label' => __('Show card within search results', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
                'sections' => ['easycredit_ratenkauf']
            ],

            'card_position' => [
                'title' => __('Position in product list', 'wc-easycredit'),
                'type' => 'number',
                'default' => '1',
                'sections' => ['easycredit_ratenkauf']
            ],

            'card_src' => [
                'title' => __('Use your own image', 'wc-easycredit'),
                'type' => 'hidden',
                'default' => '',
                'sections' => ['easycredit_ratenkauf']
            ],

            'marketing_components_flashbox' => [
                'title' => __('Marketing - Flashbox', 'wc-easycredit'),
                'type' => 'title',
                'sections' => ['easycredit_ratenkauf']
            ],

            'flashbox_enabled' => [
                'title' => __('Activate flashbox', 'wc-easycredit'),
                'label' => __('Show flashbox at the bottom of the screen', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
                'sections' => ['easycredit_ratenkauf']
            ],

            'flashbox_src' => [
                'title' => __('Use your own image', 'wc-easycredit'),
                'type' => 'hidden',
                'default' => '',
                'sections' => ['easycredit_ratenkauf']
            ],

            'marketing_components_bar' => [
                'title' => __('Marketing - Bar', 'wc-easycredit'),
                'type' => 'title',
                'sections' => ['easycredit_ratenkauf']
            ],

            'bar_enabled' => [
                'title' => __('Activate bar', 'wc-easycredit'),
                'label' => __('Show bar at the top of the screen', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
                'sections' => ['easycredit_ratenkauf']
            ],

            'transactions' => [
                'title' => __('Order Management', 'wc-easycredit'),
                'type' => 'title',
            ],

            'mark_shipped' => [
                'title' => __('Confirm shipping automatically?', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'mark_shipped_status' => [
                'title' => __('Order status to confirm shipping', 'wc-easycredit'),
                'type' => 'select',
                'default' => 'wc-completed',
                'options' => wc_get_order_statuses(),
            ],

            'mark_refunded' => [
                'title' => __('Refund automatically?', 'wc-easycredit'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'mark_refunded_status' => [
                'title' => __('Order status to refund', 'wc-easycredit'),
                'type' => 'select',
                'default' => 'wc-refunded',
                'options' => wc_get_order_statuses(),
            ],

            'clickandcollect_intro' => [
                'type' => 'clickandcollectintro',
            ],

            'marketing_components_clickandcollect' => [
                'title' => __('Click & Collect', 'wc-easycredit'),
                'type' => 'title',
            ],

            'clickandcollect_shipping_method' => [
                'title' => __('Shipping method', 'wc-easycredit'),
                'type' => 'select',
                'default' => '',
                'options' => [
                    '' => '',
                ]
            ],
        ];
    }
}
