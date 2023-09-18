<?php
if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\OrderController as LegacyOrderController;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Netzkollektiv\EasyCredit\Api\Storage;
use Psr\Log\LoggerInterface;
use Teambank\RatenkaufByEasyCreditApiV3 as ApiV3;

class WC_Gateway_RatenkaufByEasyCredit extends WC_Payment_Gateway
{
    public static $initialized = false;

    public $plugin;
    public $id;
    public $icon;
    public $instructions;
    public $debug;

    public $storage;
    public $logger;

    protected $tmp_order;


    public function __construct()
    {
        $this->plugin = wc_ratenkaufbyeasycredit();
        
        $this->id = WC_RATENKAUFBYEASYCREDIT_ID;

        $this->has_fields = false;
        $this->method_title = __('easyCredit-Ratenkauf', 'woocommerce-gateway-ratenkaufbyeasycredit');
        $this->method_description = __('easyCredit-Ratenkauf - jetzt die einfachste Teilzahlungslösung Deutschlands nutzen. Unser Credo einfach, fair und sicher gilt sowohl für Ratenkaufkunden als auch für Händler. Der schnelle, einfache und medienbruchfreie Prozess mit sofortiger Online-Bonitätsprüfung lässt sich sicher in den Onlineshop integrieren. Wir übernehmen das Ausfallrisiko und Sie können Ihren Umsatz bereits nach drei Tagen verbuchen.');

        $this->init_form_fields();
        $this->init_settings();

        $title = $this->get_option('title');
        $this->title = !empty($title) ? $title : $this->method_title;
        $this->description = '';
        $this->instructions = $this->get_option('instructions');
        $this->debug = $this->get_option('debug', false);

        $this->has_fields = true;

        $this->order_button_text = __('Continue to pay by installments', 'woocommerce-gateway-ratenkaufbyeasycredit');

        if (self::$initialized) {
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
            add_action(
                'woocommerce_ratenkaufbyeasycredit_order_item_totals',
                [$this, 'order_item_totals']
            );
        }

        if (is_admin()) {
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                [$this, 'process_admin_options']
            );
            add_action('admin_notices', [$this, 'auto_check_credentials']);
            add_action('admin_notices', [$this, 'auto_check_requirements']);
            add_action('admin_notices', [$this, 'check_review_page_exists']);
        }
        
        add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);

        self::$initialized = true;
    }

    public function admin_options()
    {
        ob_start();
        parent::admin_options();
        $parent_options = ob_get_contents();
        ob_end_clean();

        $shipping_methods = '';
        foreach (WC()->shipping()->load_shipping_methods() as $code => $method) {
            $selected = ($this->get_option('clickandcollect_shipping_method') == $method->id) ? 'selected="selected"' : '';
            $shipping_methods .= '<option value="' . $method->id . '" ' . $selected . '>' . $method->get_method_title() . '</option>';
        }

        $parent_options = preg_replace(
            '!(id="woocommerce_ratenkaufbyeasycredit_clickandcollect_shipping_method".*?>)(.+?)(</select>)!s',
            '$1$2' . $shipping_methods . '$3',
            (string)$parent_options
        );

        $marketing_settings = [
            'express_checkout', 'widget', 'modal', 'card', 'flashbox', 'bar', 'clickandcollect'
        ];
        foreach ( $marketing_settings as $marketing_setting ) {
            $html_extracted = preg_match(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_ratenkaufbyeasycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)\K(<table class="form-table">)(.+?)(</table>)!s',
                (string)$parent_options,
                $html_extracted_matches
            );
            $parent_options = preg_replace(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_ratenkaufbyeasycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)(<table class="form-table">)(.+?)(</table>)!s',
                '',
                (string)$parent_options
            );
            $parent_options = preg_replace(
                '!(class="easycredit-marketing__content__settings settings-' . $marketing_setting . '".*?>)(.+?)(</div>)!s',
                '$1' . $html_extracted_matches[0] . '$3',
                (string)$parent_options
            );
        }
        ?>
        <div class="ratenkaufbyeasycredit-wrapper">
            <?php include(dirname(__FILE__) . '/../templates/template-intro.php'); ?>
            <?php echo $parent_options; ?>
        </div>
        <?php
    }

    public function validate_fields()
    {
        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            $order = wc_get_order($wp->query_vars['order-pay']);
        } else {
            $order = $this->get_tmp_order();
        }

        try {
            $checkout = $this->get_checkout();

            $this->get_storage()->set('express', false);
            $checkout->isAvailable($this->get_quote_builder()->build($order));
        } catch (\Exception $e) {
            $error = $e->getMessage();
            wc_add_notice(
                sprintf(__(
                    '%s: ' . $error,
                    'woocommerce-gateway-ratenkaufbyeasycredit'
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
                'title' => parent::get_title(),
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

    public function payment_review_before()
    {
        if ((int)get_option('woocommerce_easycredit_checkout_review_page_id') === (int)get_queried_object_id()) {
            try {
                $this->get_checkout()->loadTransaction();
            } catch (\Exception $e) {
                return $this->handleError($e->getMessage());
            }
        }
    }

    public function payment_review()
    {
        if (is_admin()) {
            return;
        }

        $transaction = $this->get_checkout()->loadTransaction();

        if ($this->get_storage()->get('express')) {
            $this->create_express_checkout_order($transaction);
        }
 
        $order = $this->get_current_order();
        if (!$order) {
            return;
        }

        ob_start();
        $this->plugin->load_template('review-order', [
            'gateway' => $this,
            'order' => $order,
        ]);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    public function maybe_expire_order()
    {
        $order = $this->get_current_order();
        if (!$order) {
            return;
        }
        if (!WC()->session) {
            return;
        }
        
        $quote = $this->get_quote_builder()->build($order);

        $checkout = $this->get_checkout();
        if ($this->get_storage()->get('authorized_amount') != $quote->getOrderDetails()->getOrderValue()
            && !$checkout->verifyAddress($quote)
        ) {
            $checkout->clear();
        }
    }
    
    public function maybe_return_from_payment_page()
    {
        if (!isset($_GET['woo-' . $this->id . '-return'])) {
            return;
        }

        try {
            $checkout = $this->get_checkout();

            if (!$checkout->isInitialized()
                || !$checkout->isApproved()
            ) {
                throw new \Exception(__('Transaction not approved', 'woocommerce-gateway-ratenkaufbyeasycredit'));
            }
        } catch (\Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    public function maybe_order_confirm()
    {
        if (!isset($_POST['woo-' . $this->id . '-confirm'])) {
            return;
        }
       
        $order = $this->get_current_order();
        if (!$order) {
            $this->handleError('Could not find order');
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
            $checkout = $this->get_checkout();
    
            if (!$checkout->isInitialized()
                || !$checkout->isApproved()
            ) {
                throw new \Exception(__('Transaction not approved', 'woocommerce-gateway-ratenkaufbyeasycredit'));
            }

            ob_start(); // Suppress error output from akismet
    
            if (!$checkout->authorize($order->get_order_number())) {
                throw new \Exception(__('Transaction could not be captured', 'woocommerce-gateway-ratenkaufbyeasycredit'));
            }

            // check transaction status right away
            $tx = $checkout->loadTransaction($this->get_storage()->get('token'));
            if ($tx->getStatus() === ApiV3\Model\TransactionInformation::STATUS_AUTHORIZED) {
                $order->payment_complete(
                    $this->get_storage()->get('transaction_id')
                );
            }

            $order->add_meta_data($this->id . '-interest-amount', $this->get_storage()->get('interest_amount'), true);
            $order->add_meta_data($this->id . '-transaction-id', $this->get_storage()->get('transaction_id'), true);
            $order->add_meta_data($this->id . '-token', $this->get_storage()->get('token'), true);

            $order->save();
            
            WC()->cart->empty_cart();
            $checkout->clear();
            
            ob_end_clean();
            
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } catch (\Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    public function handleError($message)
    {
        $this->get_logger()->error($message);
        wc_add_notice(__($message, 'woocommerce-gateway-ratenkaufbyeasycredit'), 'error');
        $this->get_checkout()->clear();

        $url = wc_get_page_permalink('cart');
        
        $order = $this->get_current_order();
        if ($order) {
            $url = $order->get_cancel_order_url_raw();
        }
        wp_safe_redirect($url);
        exit;
    }
    
    public function auto_check_requirements()
    {
        if (!filter_var(ini_get('allow_url_fopen'), \FILTER_VALIDATE_BOOLEAN)) {
            echo $this->_display_settings_error(__('To use easyCredit-Ratenkauf the php.ini setting "allow_url_fopen" must be enabled.', 'woocommerce-gateway-ratenkaufbyeasycredit'));
        }
    }

    public function auto_check_credentials()
    {
        if (get_current_screen()->parent_base !== 'woocommerce' ||
            $this->_get_transient($this->id . '-settings-checked')
        ) {
            return;
        }


        $apiKey = $this->get_option('api_key');
        $apiToken = $this->get_option('api_token');
        $apiSignature = $this->get_option('api_signature');

        $error = $this->check_credentials($apiKey, $apiToken, $apiSignature);
        if ($error) {
            echo $this->_display_settings_error($error);
            return;
        }
        set_transient($this->id . '-settings-checked', true, DAY_IN_SECONDS);
    }

    public function check_credentials($apiKey, $apiToken, $apiSignature = null)
    {
        if (!empty($apiKey) && !empty($apiToken)) {
            try {
                try {
                    $this->get_checkout()->verifyCredentials($apiKey, $apiToken, $apiSignature);
                } catch (ApiV3\Integration\ApiCredentialsInvalidException $e) {
                    $settingsUri = admin_url('admin.php?page=wc-settings&tab=checkout&section=ratenkaufbyeasycredit');
                    return implode(' ', [
                        __('easyCredit-Ratenkauf credentials are not valid.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
                        sprintf(__('Please go to <a href="%s">plugin settings</a> and correct API Key and API Token.', 'woocommerce-gateway-ratenkaufbyeasycredit'), $settingsUri),
                    ]);
                } catch (ApiV3\Integration\ApiCredentialsNotActiveException $e) {
                    return __('Your credentials are valid, but your account has not been activated yet.', 'woocommerce-gateway-ratenkaufbyeasycredit');
                } catch (ApiV3\ApiException $e) {
                    if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                        $messages = [];
                        foreach ($e->getResponseObject()->getViolations() as $violation) {
                            $messages[] = $violation->getMessageDE() ? $violation->getMessageDE() :  $violation->getMessage();
                        }
                        return implode(' ', [
                            __('easyCredit-Ratenkauf credentials are not valid.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
                            sprintf(__('An error occured while checking your credentials: %s', 'woocommerce-gateway-ratenkaufbyeasycredit'), implode(', ', $messages)),
                        ]);
                    }
                    throw $e;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
                return sprintf(__('An error occured while checking your credentials: %s', 'woocommerce-gateway-ratenkaufbyeasycredit'), $e->getMessage());
            }
        } else {
            return __('Please enter your credentials to use easyCredit-Ratenkauf payment plugin in the <a href="%s">plugin settings</a>.', 'woocommerce-gateway-ratenkaufbyeasycredit');
        }
    }

    public function check_review_page_exists()
    {
        if (get_current_screen()->parent_base !== 'woocommerce') {
            return;
        }

        $page_path = current($this->plugin->get_review_page_data())['name'];
        if (get_page_by_path($page_path, OBJECT)) {
            return;
        }

        echo $this->_display_settings_error(
            __('The "easyCredit-Ratenkauf" review page does not exist. Probably it was deleted by mistake. The page is necessary to confirm "easyCredit-Ratenkauf" payments after being returned from the payment terminal. To restore the page, please restore it from the trash under "Pages", or deactivate and activate the plugin in the <a href="%s">plugin administration</a>.', 'woocommerce-gateway-ratenkaufbyeasycredit'),
            is_multisite() ? admin_url('network/plugins.php?s=easycredit') : admin_url('plugins.php?s=easycredit')
        );
        return;
    }

    public function abort_create_order($order)
    {
        $this->tmp_order = $order;
        throw new Exception(__CLASS__ . '_tmp_order');
    }

    public function prevent_remove_items()
    {
        return false;
    }

    public function get_tmp_order()
    {
        add_action('woocommerce_checkout_create_order', [$this, 'abort_create_order']);
        add_filter('woocommerce_order_has_status', [$this, 'prevent_remove_items']);

        $wc_checkout = WC_Checkout::instance();
        $postData = [];
        if (isset($_POST['post_data'])) {
            parse_str($_POST['post_data'], $postData);
        } else {
            $postData = $_POST;
        }
        $postData['payment_method'] = 'easycredit';

        $wc_checkout->create_order($postData);

        remove_filter('woocommerce_order_has_status', [$this, 'prevent_remove_items']);
        remove_action('woocommerce_checkout_create_order', [$this, 'abort_create_order'], 10);

        $order = $this->tmp_order;
        if ($order && isset($postData['ship_to_different_address'])) {
            $order->add_meta_data('ship_to_different_address', $postData['ship_to_different_address']);
        }
        return $order;
    }

    public function payment_fields()
    {
        $error = false;
        $checkout = $this->get_checkout();

        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            $order = wc_get_order($wp->query_vars['order-pay']);
        } else {
            $order = $this->get_tmp_order();
        }

        if (is_null($order)) {
            return;
        }

        try {
            $this->get_storage()->set('express', 0);
            $quote = $this->get_quote_builder()->build($order);
            $checkout->isAvailable($quote);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (isset($quote) &&
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
        ]);
    }

    public function init_form_fields()
    {
        $fields = require(wc_ratenkaufbyeasycredit()->includes_path . 'admin-fields.php');
        $fields = apply_filters('wc_ratenkaufbyeasycredit_form_fields', $fields);
        $this->form_fields = $fields;
    }

    public function generate_marketingintro_html()
    {
        ob_start();
        include(dirname(__FILE__) . '/../templates/template-marketing.php');
        $contents = ob_get_clean();

        return $contents;
    }

    public function generate_clickandcollectintro_html()
    {
        ob_start();
        include(dirname(__FILE__) . '/../templates/template-click-and-collect.php');
        $contents = ob_get_clean();

        return $contents;
    }

    public function get_option($key, $empty_value = null)
    {
        $option = parent::get_option($key, $empty_value);
        if ($key == 'api_verify_credentials') {
            // always return default value for button
            return $this->get_field_default(
                $this->get_form_fields()[$key]
            );
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
        if ($this->instructions &&
            !$sent_to_admin &&
            $this->id === $order->payment_method
        ) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
    
    public function get_storage()
    {
        if ($this->storage == null) {
            $this->storage = new \Netzkollektiv\EasyCredit\Api\Storage(
                WC()->session,
                $this->get_logger()
            );
        }
        return $this->storage;
    }

    public function get_logger()
    {
        if ($this->logger == null) {
            $this->logger = new \Netzkollektiv\EasyCredit\Api\Logger($this);
        }
        return $this->logger;
    }

    public function get_config()
    {
        return ApiV3\Configuration::getDefaultConfiguration()
            ->setHost('https://ratenkauf.easycredit.de')
            ->setUsername($this->get_option('api_key'))
            ->setPassword($this->get_option('api_token'))
            ->setAccessToken($this->get_option('api_signature'));
    }

    public function get_checkout()
    {
        $logger = $this->get_logger();
        $config = $this->get_config();

        $client = new ApiV3\Client($logger);

        $webshopApi = new ApiV3\Service\WebshopApi(
            $client,
            $config
        );
        $transactionApi = new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
        $installmentPlanApi = new ApiV3\Service\InstallmentplanApi(
            $client,
            $config
        );

        return new ApiV3\Integration\Checkout(
            $webshopApi,
            $transactionApi,
            $installmentPlanApi,
            $this->get_storage(),
            new ApiV3\Integration\Util\AddressValidator(),
            new ApiV3\Integration\Util\PrefixConverter(),
            $this->get_logger()
        );
    }

    public function get_quote_builder()
    {
        return new \Netzkollektiv\EasyCredit\Api\QuoteBuilder(
            $this,
            $this->get_storage()
        );
    }

    public function get_merchant_client()
    {
        $logger = $this->get_logger();
        $config = $this->get_config()
            ->setHost('https://partner.easycredit-ratenkauf.de');
        $client = new ApiV3\Client($logger);

        return new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
    }

    public function get_confirm_url()
    {
        $query_args = [
            'woo-' . $this->id . '-return' => true,
        ];
        return add_query_arg($query_args, $this->plugin->get_review_page_uri());
    }
    
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            $quote = $this->get_quote_builder()->build($order);

            $checkout = $this->get_checkout();
            $checkout->start($quote);
        } catch (ApiV3\ApiException $e) {
            $messages = [];
            if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                foreach ($e->getResponseObject()->getViolations() as $violation) {
                    $messages[] = $violation->getMessageDE() ? $violation->getMessageDE() :  $violation->getMessage();
                }
            }
            throw new Exception(sprintf(__(
                'Could not initialize easycredit payment: %s',
                'woocommerce-gateway-ratenkaufbyeasycredit'
            ), implode(', ', $messages)));
        } catch (\Exception $e) {
            throw new Exception(__(
                'Could not initialize easycredit payment',
                'woocommerce-gateway-ratenkaufbyeasycredit'
            ));
        }

        $this->get_storage()
            ->set('order_id', $order_id)
            ->set('return_url', $this->get_return_url($order))
        ;

        $paymentPageUrl = $checkout->getRedirectUrl();

        if (!$paymentPageUrl) {
            throw new Exception(__(
                'Payment Page URI could not be retrieved',
                'woocommerce-gateway-ratenkaufbyeasycredit'
            ));
        }

        return [
            'result' => 'success',
            'redirect' => $paymentPageUrl,
        ];
    }
    
    public function order_item_totals($order)
    {
        $interest = $this->get_storage()->get('interest_amount');
    
        $_totals = [];
        foreach ($order->get_order_item_totals() as $key => $total) {
            if ($key == 'payment_method') {
                continue;
            }
            if ($key == 'order_total') {
                $_totals['interest'] = [
                    'label' => __('Interest:', 'woocommerce-gateway-ratenkaufbyeasycredit'),
                    'value' => wc_price($interest, ['currency', $order->get_currency()]),
                ];
                $total['value'] = $this->get_total_including_interest($order);
            }
            $_totals[$key] = $total;
        }
        return $_totals;
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

    protected function get_current_order()
    {
        $order_id = $this->get_storage()->get('order_id');
        if (!$order_id) {
            return false;
        }
           
        return wc_get_order($order_id);
    }

    protected function create_express_checkout_order($transaction)
    {
        $updateAddress = function ($address, $type = 'billing') {
            $fields = [
                'first_name' => $address->getFirstName(),
                'last_name' => $address->getLastName(),
                'address_1' => $address->getAddress(),
                'address_2' => '',
                'city' => $address->getCity(),
                'state' => '',
                'postcode' => $address->getZip(),
                'country' => $address->getCountry(),
            ];
            array_walk($fields, function ($value, $field) use ($type) {
                WC()->customer->{"set_{$type}_{$field}"}($value);
            });
        };
        $updateAddress($transaction->getTransaction()->getOrderDetails()->getInvoiceAddress());
        $updateAddress($transaction->getTransaction()->getOrderDetails()->getShippingAddress(), 'shipping');

        $contact = $transaction->getTransaction()->getCustomer()->getContact();
        WC()->customer->set_billing_phone($contact->getMobilePhoneNumber());
        WC()->customer->set_billing_email($contact->getEmail());
        WC()->customer->save();

        $order_data = [];
        foreach (['billing', 'shipping'] as $prefix) {
            array_walk(WC()->customer->get_data()[$prefix], function ($value, $field) use (&$order_data, $prefix) {
                $order_data[$prefix . '_' . $field] = $value;
            });
        }

        $order = new \WC_Order();
        $order->set_created_via('easycredit-express-checkout');
        $order->add_order_note(__('Created via express checkout', 'woocommerce-gateway-ratenkaufbyeasycredit'));
        $order->set_payment_method($this->id);

        if (class_exists(OrderController::class)) {
            $orderController = new OrderController();
            $orderController->update_order_from_cart($order);
        } elseif (class_exists(LegacyOrderController::class)) {
            $orderController = new LegacyOrderController();
            $orderController->update_order_from_cart($order);
        }

        $this->get_storage()
            ->set('order_id', $order->get_id())
            ->set('express', false);
    }

    protected function _get_transient($name)
    {
        return ($this->debug) ? false : get_transient($name);
    }

    protected function _display_settings_error($msg, $uri = null)
    {
        if (is_array($msg)) {
            $msg = implode(' ', $msg);
        }

        if ($uri === null) {
            $uri = admin_url('admin.php?page=wc-settings&tab=checkout&section=ratenkaufbyeasycredit');
        }
        return implode([
            '<div class="error"><p>',
            sprintf($msg, $uri),
            '</p></div>',
        ]);
    }

    protected function get_total_including_interest($order)
    {
        $interest = $this->get_storage()->get('interest_amount');

        $total = $order->get_total();
        $order->set_total($total + $interest);
        $_total = $order->get_formatted_order_total();
        $order->set_total($total);
        
        return $_total;
    }
}
