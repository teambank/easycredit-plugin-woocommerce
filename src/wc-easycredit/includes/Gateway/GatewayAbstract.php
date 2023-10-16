<?php
namespace Netzkollektiv\EasyCredit\Gateway;

use Teambank\RatenkaufByEasyCreditApiV3 as ApiV3;

use Netzkollektiv\EasyCredit\Config\FieldProvider;
use Netzkollektiv\EasyCredit\Integration;
use Netzkollektiv\EasyCredit\Plugin;

abstract class GatewayAbstract extends \WC_Payment_Gateway
{
    public static $initialized = false;

    protected $fieldProvider;
    protected $integration;

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
        FieldProvider $fieldProvider
    ) {
        $this->_construct();

        $this->plugin = $plugin;
        $this->integration = $integration;
        $this->fieldProvider = $fieldProvider;

        $this->has_fields = true;
        $this->init_form_fields();
        $this->init_settings();

        $title = $this->get_option('title');
        $this->title = !empty($title) ? $title : $this->method_title;

        $this->description = '';
        $this->instructions = $this->get_option('instructions');
        $this->debug = $this->get_option('debug', false);

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
                'woocommerce_easycredit_order_item_totals',
                [$this, 'order_item_totals']
            );
        }

        if (is_admin()) {
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                [$this, 'process_admin_options']
            );
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
        foreach (WC()->shipping()->load_shipping_methods() as $method) {
            $selected = ($this->get_option('clickandcollect_shipping_method') == $method->id) ? 'selected="selected"' : '';
            $shipping_methods .= '<option value="' . $method->id . '" ' . $selected . '>' . $method->get_method_title() . '</option>';
        }

        $parent_options = preg_replace(
            '!(id="woocommerce_easycredit_clickandcollect_shipping_method".*?>)(.+?)(</select>)!s',
            '$1$2' . $shipping_methods . '$3',
            (string)$parent_options
        );

        $marketing_settings = [
            'express_checkout', 'widget', 'modal', 'card', 'flashbox', 'bar', 'clickandcollect'
        ];
        foreach ($marketing_settings as $marketing_setting) {
            preg_match(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_easycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)\K(<table class="form-table">)(.+?)(</table>)!s',
                (string)$parent_options,
                $html_extracted_matches
            );
            $parent_options = preg_replace(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_easycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)(<table class="form-table">)(.+?)(</table>)!s',
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
        <div class="easycredit-wrapper">
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

            $checkout = $this->integration->checkout();
            $this->get_storage()->set('express', false);
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
        if (!isset($_GET['woo-' . $this->id . '-return'])) {
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
        if (!isset($_POST['woo-' . $this->id . '-confirm'])) {
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

            // check transaction status right away
            $tx = $checkout->loadTransaction($this->integration()->storage()->get('token'));
            if ($tx->getStatus() === ApiV3\Model\TransactionInformation::STATUS_AUTHORIZED) {
                $order->payment_complete(
                    $this->integration()->storage()->get('transaction_id')
                );
            }

            $storage = $this->integration->storage();
            
            $order->add_meta_data($this->id . '-interest-amount', $storage->get('interest_amount'), true);
            $order->add_meta_data($this->id . '-transaction-id',$storage->get('transaction_id'), true);
            $order->add_meta_data($this->id . '-token', $storage->get('token'), true);

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
                        __('easyCredit-Ratenkauf credentials are not valid.', 'wc-easycredit'),
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
                            __('easyCredit-Ratenkauf credentials are not valid.', 'wc-easycredit'),
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
            return __('Please enter your credentials to use easyCredit-Ratenkauf payment plugin in the <a href="%s">plugin settings</a>.', 'wc-easycredit');
        }
    }

    public function abort_create_order($order)
    {
        $this->tmp_order = $order;
        throw new \Exception(__CLASS__ . '_tmp_order');
    }

    public function prevent_remove_items()
    {
        return false;
    }

    public function get_tmp_order()
    {
        add_action('woocommerce_checkout_create_order', [$this, 'abort_create_order']);
        add_filter('woocommerce_order_has_status', [$this, 'prevent_remove_items']);

        $wc_checkout = \WC_Checkout::instance();
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
        $checkout = $this->integration->checkout();

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
        ]);
    }

    public function init_form_fields()
    {
        $fields = $this->fieldProvider->get_fields_by_section($this->id);
        $fields = apply_filters('wc_easycredit_form_fields', $fields);
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
        if (
            $this->instructions &&
            !$sent_to_admin &&
            $this->id === $order->payment_method
        ) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
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

    public function order_item_totals($order)
    {
        $interest = $this->integration->storage()->get('interest_amount');

        $_totals = [];
        foreach ($order->get_order_item_totals() as $key => $total) {
            if ($key == 'payment_method') {
                continue;
            }
            if ($key == 'order_total') {
                $_totals['interest'] = [
                    'label' => __('Interest:', 'wc-easycredit'),
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

    protected function get_total_including_interest($order)
    {
        $interest = $this->integration->storage()->get('interest_amount');

        $total = $order->get_total();
        $order->set_total($total + $interest);
        $_total = $order->get_formatted_order_total();
        $order->set_total($total);

        return $_total;
    }
}
