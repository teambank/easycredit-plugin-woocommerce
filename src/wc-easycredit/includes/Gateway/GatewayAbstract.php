<?php

namespace Netzkollektiv\EasyCredit\Gateway;

use Teambank\EasyCreditApiV3 as ApiV3;

use Netzkollektiv\EasyCredit\Config\FieldProvider;
use Netzkollektiv\EasyCredit\Integration;
use Netzkollektiv\EasyCredit\Plugin;
use Netzkollektiv\EasyCredit\Helper\TemporaryOrder;

abstract class GatewayAbstract extends \WC_Payment_Gateway
{
    public $PAYMENT_TYPE = null;

    public static $initialized = [
        'easycredit_ratenkauf' => false,
        'easycredit_rechnung' => false
    ];

    protected $integration;
    protected $fieldProvider;
    protected $temporaryOrderHelper;

    public $plugin;
    public $id;
    public $icon;
    public $instructions;
    public $debug;

    public $storage;
    public $logger;

    protected $tmp_order;

    abstract function _construct();

    public function __construct(
        Plugin $plugin,
        Integration $integration,
        FieldProvider $fieldProvider,
        TemporaryOrder $temporaryOrderHelper
    ) {
        $this->_construct();

        $this->plugin = $plugin;
        $this->integration = $integration;
        $this->fieldProvider = $fieldProvider;
        $this->temporaryOrderHelper = $temporaryOrderHelper;

        $this->has_fields = true;

        add_action('init', function() {
            $this->init_form_fields();
            $this->init_settings();

            $title = $this->get_option('title');
            $this->title = !empty($title) ? $title : $this->method_title;

            $this->description = '';
            $this->instructions = $this->get_option('instructions');
            $this->debug = $this->get_option('debug', false);
        });

        if (self::$initialized[$this->id]) {
            return; // initialize payment gateway only once, e.g. WPML Woocommerce tries to initialize again which results in duplicate/wrong behavior
        }

        if (!is_admin()) {
            add_action('wp', [$this, 'maybe_expire_order']);
            add_action('wp', [$this, 'maybe_return_from_payment_page']);
            add_action('wp', [$this, 'maybe_order_confirm']);

            add_action(
                'woocommerce_checkout_create_order',
                [$this, 'proccess_payment_order_details']
            );
            add_action(
                'woocommerce_before_pay_action',
                [$this, 'proccess_payment_order_details']
            );
        }

        if (is_admin()) {
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                [$this, 'process_admin_options']
            );
        }

        add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);

        self::$initialized[$this->id] = true;
    }

    public function validate_fields()
    {
        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            $order = wc_get_order($wp->query_vars['order-pay']);
        } else {
            $order = $this->temporaryOrderHelper->get_order();
        }

        try {
            $this->integration->storage()
                ->set('express', false);

            $checkout = $this->integration->checkout();
            $checkout->isAvailable($this->integration->quote_builder()->build($order));
        } catch (\Exception $e) {
            $error = $e->getMessage();
            wc_add_notice(
                sprintf(__(
                    '%s: ' . $error,
                    'wc-easycredit'
                ), $this->get_title()),
                'error'
            );

            return false;
        }
        return true;
    }

    public function get_title()
    {
        $backtrace = debug_backtrace();
        if ($backtrace[1]['function'] == 'include') {
            $this->plugin->load_template('payment-method-title', [
                'paymentType' => str_replace('_PAYMENT', '', $this->PAYMENT_TYPE),
                'label' => parent::get_title(),
                'slogan' => $this->get_option('subtitle'),
            ]);
            return '';
        }
        return parent::get_title();
    }

    public function get_icon()
    {
        $backtrace = debug_backtrace();
        if ($backtrace[1]['function'] == 'include') {
            return '';
        }
        return parent::get_icon();
    }

    public function maybe_expire_order()
    {
        $order = $this->plugin->get_current_order();
        if (!$order) {
            return;
        }
        if (!WC()->session) {
            return;
        }

        $quote = $this->integration->quote_builder()->build($order);

        $checkout = $this->integration->checkout();
        if (
            $this->integration->storage()->get('authorized_amount') != $quote->getOrderDetails()->getOrderValue()
            && !$checkout->verifyAddress($quote)
        ) {
            $checkout->clear();
        }
    }

    public function maybe_return_from_payment_page()
    {
        if (!isset($_GET['woo-' . $this->plugin->id . '-return'])) {
            return;
        }

        try {
            $checkout = $this->integration->checkout();

            if (
                !$checkout->isInitialized()
                || !$checkout->isApproved()
            ) {
                throw new \Exception(__('Transaction not approved', 'wc-easycredit'));
            }
        } catch (\Exception $e) {
            $this->plugin->handleError($e->getMessage());
        }
    }

    public function maybe_order_confirm()
    {
        if (!isset($_POST['woo-' . $this->plugin->id . '-confirm'])) {
            return;
        }

        $order = $this->plugin->get_current_order();
        if (!$order) {
            $this->plugin->handleError('Could not find order');
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-easycredit-pay')) {
            wc_add_notice(__('Could not verify nonce', 'woocommerce'), 'error');
            return;
        }

        if (empty((int) isset($_POST['terms'])) && !empty((int) isset($_POST['terms-field']))) {
            wc_add_notice(__('Please read and accept the terms and conditions to proceed with your order.', 'woocommerce'), 'error');
            return;
        }

        try {
            $checkout = $this->integration->checkout();

            if (
                !$checkout->isInitialized()
                || !$checkout->isApproved()
            ) {
                throw new \Exception(__('Transaction not approved', 'wc-easycredit'));
            }

            ob_start(); // Suppress error output from akismet

            if (!$checkout->authorize($order->get_order_number())) {
                throw new \Exception(__('Transaction could not be captured', 'wc-easycredit'));
            }

            $storage = $this->integration->storage();

            // check transaction status right away
            $tx = $checkout->loadTransaction($storage->get('token'));
            if ($tx->getStatus() === ApiV3\Model\TransactionInformation::STATUS_AUTHORIZED) {
                $order->payment_complete(
                    $storage->get('transaction_id')
                );
            }

            $order->add_meta_data(Plugin::META_KEY_TOKEN, $storage->get('token'), true);
            $order->add_meta_data(Plugin::META_KEY_INTEREST_AMOUNT, $storage->get('interest_amount'), true);
            $order->add_meta_data(Plugin::META_KEY_TRANSACTION_ID, $storage->get('transaction_id'), true);

            $order->save();

            WC()->cart->empty_cart();
            $checkout->clear();

            ob_end_clean();

            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } catch (\Exception $e) {
            $this->plugin->handleError($e->getMessage());
        }
    }

    public function check_credentials($apiKey, $apiToken, $apiSignature = null)
    {
        if (!empty($apiKey) && !empty($apiToken)) {
            try {
                try {
                    $this->integration->checkout()->verifyCredentials($apiKey, $apiToken, $apiSignature);
                } catch (ApiV3\Integration\ApiCredentialsInvalidException $e) {
                    $settingsUri = admin_url('admin.php?page=wc-settings&tab=checkout&section=easycredit');
                    return implode(' ', [
                        __('easyCredit payment credentials are not valid.', 'wc-easycredit'),
                        sprintf(__('Please go to <a href="%s">plugin settings</a> and correct API Key and API Token.', 'wc-easycredit'), $settingsUri),
                    ]);
                } catch (ApiV3\Integration\ApiCredentialsNotActiveException $e) {
                    return __('Your credentials are valid, but your account has not been activated yet.', 'wc-easycredit');
                } catch (ApiV3\ApiException $e) {
                    if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                        $messages = [];
                        foreach ($e->getResponseObject()->getViolations() as $violation) {
                            $messages[] = $violation->getMessageDE() ? $violation->getMessageDE() :  $violation->getMessage();
                        }
                        return implode(' ', [
                            __('easyCredit pamyent credentials are not valid.', 'wc-easycredit'),
                            sprintf(__('An error occured while checking your credentials: %s', 'wc-easycredit'), implode(', ', $messages)),
                        ]);
                    }
                    throw $e;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
                return sprintf(__('An error occured while checking your credentials: %s', 'wc-easycredit'), $e->getMessage());
            }
        } else {
            return __('Please enter your credentials in the <a href="%s">plugin settings</a> to use the easyCredit payment plugin.', 'wc-easycredit');
        }
    }

    public function payment_fields()
    {
        $error = false;
        $checkout = $this->integration->checkout();

        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            $order = wc_get_order($wp->query_vars['order-pay']);
        } else {
            $order = $this->temporaryOrderHelper->get_order(
                $this->PAYMENT_TYPE
            );
        }

        if (is_null($order)) {
            return;
        }

        try {
            $this->integration->storage()->set('express', 0);
            $quote = $this->integration->quote_builder()->build($order);
            $checkout->isAvailable($quote);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (
            isset($quote) &&
            $quote->getOrderDetails()->getInvoiceAddress()->getCountry() != 'DE' &&
            $quote->getOrderDetails()->getInvoiceAddress()->getCountry() != ''
        ) {
            $error = 'easyCredit-Ratenkauf ist leider nur in Deutschland verfügbar.';
        }

        $this->plugin->load_template('payment-fields', [
            'easyCredit' => $this,
            'easyCreditWebshopId' => $this->get_option('api_key'),
            'easyCreditAmount' => isset($quote) ? $quote->getOrderDetails()->getOrderValue() : 0,
            'easyCreditError' => $error,
            'easyCreditPaymentType' => $this->PAYMENT_TYPE,
        ]);
    }

    public function init_form_fields()
    {
        $fields = $this->fieldProvider->get_fields_by_section($this->id);
        $fields = apply_filters('wc_easycredit_form_fields', $fields);
        $this->form_fields = $fields;
    }

    public function get_option($key, $empty_value = '')
    {
        $option = parent::get_option($key, $empty_value);
        if ($key == 'api_verify_credentials') {
            // always return default value for button
            return $this->get_field_default(
                $this->get_form_fields()[$key]
            );
        }

        if ('' === $option) {
            $option = $this->plugin->get_option($key, $empty_value);
        }
        return $option;
    }

    public function thankyou_page()
    {
        if ($this->instructions) {
            echo wpautop(wptexturize($this->instructions));
        }
    }

    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if (
            $this->instructions &&
            !$sent_to_admin &&
            $this->id === $order->payment_method
        ) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            $postData = $_POST['easycredit'] ?? [];
            if (isset($postData['numberOfInstallments'])) {
                $this->integration
                    ->storage()
                    ->set('numberOfInstallments', intval($postData['numberOfInstallments']));
            }

            $quote = $this->integration->quote_builder()->build($order);

            $checkout = $this->integration->checkout();
            $checkout->start($quote);
        } catch (ApiV3\ApiException $e) {
            $messages = [];
            if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                foreach ($e->getResponseObject()->getViolations() as $violation) {
                    $messages[] = $violation->getMessageDE() ? $violation->getMessageDE() :  $violation->getMessage();
                }
            }
            throw new \Exception(sprintf(__(
                'Could not initialize easycredit payment: %s',
                'wc-easycredit'
            ), implode(', ', $messages)));
        } catch (\Exception $e) {
            throw new \Exception(__(
                'Could not initialize easycredit payment',
                'wc-easycredit'
            ));
        }

        $this->integration->storage()
            ->set('order_id', $order_id)
            ->set('return_url', $this->get_return_url($order));

        $paymentPageUrl = $checkout->getRedirectUrl();

        if (!$paymentPageUrl) {
            throw new \Exception(__(
                'Payment Page URI could not be retrieved',
                'wc-easycredit'
            ));
        }

        return [
            'result' => 'success',
            'redirect' => $paymentPageUrl,
        ];
    }

    public function proccess_payment_order_details($order)
    {
        foreach (['prefix'] as $attr) {
            $key = $this->id . '-' . $attr;
            if (isset($_POST[$key])) {
                $order->add_meta_data($key, $_POST[$key], true);
            }
        }
    }
}
