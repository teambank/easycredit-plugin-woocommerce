<?php

namespace Netzkollektiv\EasyCredit\Gateway;

use Teambank\EasyCreditApiV3 as ApiV3;

use Netzkollektiv\EasyCredit\Compatibility\DiviMaybeExpireCompatibility;
use Netzkollektiv\EasyCredit\Config\FieldProvider;
use Netzkollektiv\EasyCredit\Integration;
use Netzkollektiv\EasyCredit\InterestFeeHandler;
use Netzkollektiv\EasyCredit\Plugin;

abstract class GatewayAbstract extends \WC_Payment_Gateway
{
    public $PAYMENT_TYPE = null;

    public static $initialized = [
        'easycredit_ratenkauf' => false,
        'easycredit_rechnung' => false
    ];

    private static $pending_order_hooks_registered = false;

    protected $integration;
    protected $fieldProvider;
    public $plugin;
    public $id;
    public $icon;
    public $instructions;
    public $debug;

    public $storage;
    protected $tmp_order;
    abstract function _construct();
    public function __construct(
        Plugin $plugin,
        Integration $integration,
        FieldProvider $fieldProvider
    ) {
        $this->plugin = $plugin;
        $this->integration = $integration;
        $this->fieldProvider = $fieldProvider;

        $this->_construct();

        $this->icon = $this->plugin->plugin_url . '/assets/img/easycredit-supersign.svg';
        $this->has_fields = true;

        add_action('init', function () {
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
            add_action('woocommerce_after_calculate_totals', [$this, 'maybe_expire']);
            add_action(
                'woocommerce_cart_calculate_fees',
                [$this, 'add_interest_fee']
            );

            if (!self::$pending_order_hooks_registered) {
                self::$pending_order_hooks_registered = true;
                add_filter('woocommerce_product_is_in_stock', [self::class, 'treat_reserved_stock_as_in_stock'], 10, 2);
            }
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
        try {
            $this->integration->storage()
                ->set('express', false);

            $checkout = $this->integration->checkout();
            $checkout->isAvailable($this->build_quote_from_context());
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

    public function maybe_expire()
    {
        global $wp_query;

        // Don't expire during easycredit_action requests.
        if ($wp_query && $wp_query->get('easycredit_action')) {
            return;
        }

        // Don't expire transactions while on the customer payment page (order-pay),
        // otherwise a successfully initialized/approved transaction might be cleared
        // before the customer clicks "Pay for order".
        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            return;
        }

        if (!WC()->session) {
            return;
        }

        // skip if authorized amount is not set
        $storage = $this->integration->storage();
        $authorizedAmount = $storage->get('authorized_amount');
        if ($authorizedAmount === null) {
            return;
        }

        // Divi's Additional Info checkout module can trigger an intermediate totals
        // calculation (shipping zeroed briefly) during render; skip expiry there.
        if (DiviMaybeExpireCompatibility::shouldSkipExpire()) {
            return;
        }

        $checkout = $this->integration->checkout();
        $quote = $this->build_quote_from_context();

        if (!$checkout->isAmountValid($quote)) {
            $checkout->clear();
            return;
        }

        if (!$checkout->verifyAddress($quote)) {
            $checkout->clear();
            return;
        }
    }

    /**
     * Add the EasyCredit interest fee to the cart when the corresponding gateway
     * is selected. This is tied to the woocommerce_cart_calculate_fees action.
     */
    public function add_interest_fee()
    {
        if (!WC()->cart || !WC()->session) {
            return;
        }

        // Check if easyCredit payment method is selected
        $chosen_payment_method = WC()->session->get('chosen_payment_method');
        if (!$chosen_payment_method || !$this->plugin->is_easycredit_method($chosen_payment_method)) {
            return;
        }

        $interest_amount = $this->integration->storage()->get('interest_amount');
        if ($interest_amount === null || (float) $interest_amount <= 0) {
            return;
        }

        InterestFeeHandler::add_to_cart((float) $interest_amount);
    }

    /**
     * Build the appropriate EasyCredit transaction "quote" based on context.
     * On the customer payment page (order-pay) we must use the existing order;
     * otherwise we fall back to the current cart.
     */
    protected function build_quote_from_context()
    {
        global $wp;

        if (isset($wp->query_vars['order-pay'])) {
            $order = wc_get_order($wp->query_vars['order-pay']);
            if ($order) {
                return $this->integration->order_builder()->build($order);
            }
        }

        return $this->integration->quote_builder()->build();
    }

    public function payment_fields()
    {
        $error = false;
        $checkout = $this->integration->checkout();

        try {
            $this->integration->storage()->set('express', false);

            $quote = $this->build_quote_from_context();
            $checkout->isAvailable($quote);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (
            isset($quote) &&
            $quote->getOrderDetails()->getInvoiceAddress() &&
            $quote->getOrderDetails()->getInvoiceAddress()->getCountry() != 'DE' &&
            $quote->getOrderDetails()->getInvoiceAddress()->getCountry() != ''
        ) {
            $error = 'easyCredit-Ratenkauf ist leider nur in Deutschland verfügbar.';
        }

        // Show payment plan summary when: (POST with this method) or (order-pay and summary in storage for this order)
        $showSummary = (isset($_POST['payment_method']) && $_POST['payment_method'] === $this->id);
        if (!$showSummary) {
            global $wp;
            if (isset($wp->query_vars['order-pay'])) {
                $order = wc_get_order($wp->query_vars['order-pay']);
                $showSummary = $order && $order->get_payment_method() === $this->id && $this->integration->storage()->get('summary');
            }
        }
        $easyCreditPaymentPlan = $showSummary ? $this->integration->storage()->get('summary') : null;

        $this->plugin->load_template('payment-fields', [
            'easyCredit' => $this,
            'easyCreditWebshopId' => $this->plugin->get_option('api_key'),
            'easyCreditAmount' => isset($quote) ? $quote->getOrderDetails()->getOrderValue() : 0,
            'easyCreditError' => $error,
            'easyCreditPaymentPlan' => $easyCreditPaymentPlan,
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

        // Track whether this payment was started from the customer payment page
        // so that the success handler (easycredit/return) can redirect back there.
        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            $this->integration->storage()->set('order_pay_url', \esc_url_raw($order->get_checkout_payment_url()));
        } else {
            $this->integration->storage()->set('order_pay_url', null);
        }

        // Store number of installments if provided
        $postData = $_POST['easycredit'] ?? [];
        if (isset($postData['numberOfInstallments'])) {
            $this->integration->storage()->set('numberOfInstallments', intval($postData['numberOfInstallments']));
        }

        $checkout = $this->integration->checkout();

        $quote = $this->integration->order_builder()->build($order);

        // Handle already approved payment (customer returned from payment gateway)
        if ($checkout->isInitialized() && $this->tryCompleteApprovedPayment($checkout, $order, $quote)) {
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        // Initialize new payment and redirect to payment gateway
        try {
            // For order-pay re-initializations, ensure any previous interest fee
            // is removed from the order so that the initialization amount never
            // includes the interest.
            if (isset($wp->query_vars['order-pay']) && !$checkout->isInitialized()) {
                InterestFeeHandler::remove_from_order($order);
                $quote = $this->integration->order_builder()->build($order);
            }

            $checkout->start($quote);
        } catch (ApiV3\ApiException $e) {
            $messages = [];
            if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                foreach ($e->getResponseObject()->getViolations() as $violation) {
                    $messages[] = $violation->getMessageDE() ?: $violation->getMessage();
                }
            }
            throw new \Exception(sprintf(
                __('Could not initialize easycredit payment: %s', 'wc-easycredit'),
                implode(', ', $messages)
            ));
        } catch (\Exception $e) {
            throw new \Exception(__('Could not initialize easycredit payment', 'wc-easycredit'));
        }

        $this->persist_draft_order_before_redirect((int) $order_id);

        $paymentPageUrl = $checkout->getRedirectUrl();

        if (!$paymentPageUrl) {
            throw new \Exception(__('Payment Page URI could not be retrieved', 'wc-easycredit'));
        }

        return [
            'result' => 'success',
            'redirect' => $paymentPageUrl,
        ];
    }

    /**
     * Attempts to complete an already approved payment.
     * Returns true if payment was completed, false otherwise.
     */
    private function tryCompleteApprovedPayment($checkout, $order, $quote)
    {
        // Check if payment is approved and valid
        if (!$checkout->isApproved() || !$checkout->isValid($quote)) {
            return false;
        }

        // Complete the order
        ob_start(); // Suppress error output from akismet

        if (!$checkout->authorize($order->get_order_number())) {
            ob_end_clean();
            throw new \Exception(__('Transaction could not be captured', 'wc-easycredit'));
        }

        $storage = $this->integration->storage();
        $tx = $checkout->loadTransaction($storage->get('token'));

        if ($tx->getStatus() === ApiV3\Model\TransactionInformation::STATUS_AUTHORIZED) {
            $order->payment_complete($storage->get('transaction_id'));
        }

        $order->add_meta_data(Plugin::META_KEY_TOKEN, $storage->get('token'), true);
        $order->add_meta_data(Plugin::META_KEY_INTEREST_AMOUNT, $storage->get('interest_amount'), true);
        $order->add_meta_data(Plugin::META_KEY_TRANSACTION_ID, $storage->get('transaction_id'), true);
        $order->save();

        WC()->cart->empty_cart();
        $checkout->clear();

        $this->remove_interest_after_payment($order);

        ob_end_clean();

        return true;
    }

    /**
     * Re-affirm the block-checkout draft order before redirecting to easyCredit.
     * Order-pay uses order_awaiting_payment, which WooCommerce sets before gateways run.
     */
    protected function persist_draft_order_before_redirect(int $order_id): void
    {
        if ($order_id <= 0 || !WC()->session) {
            return;
        }

        global $wp;
        if (isset($wp->query_vars['order-pay'])) {
            return;
        }

        $current_draft_id = (int) WC()->session->get('store_api_draft_order', 0);
        if ($current_draft_id === $order_id) {
            return;
        }

        WC()->session->set('store_api_draft_order', $order_id);
        WC()->session->save_data();
    }

    /**
     * Pending order id while the customer is finishing easyCredit financing.
     */
    public static function get_pending_order_id(): int
    {
        if (!function_exists('WC') || !WC()->session) {
            return 0;
        }

        $order_id = (int) WC()->session->get('store_api_draft_order', 0);
        if ($order_id <= 0 && isset(WC()->session->order_awaiting_payment)) {
            $order_id = (int) WC()->session->order_awaiting_payment;
        }

        if ($order_id <= 0) {
            return 0;
        }

        $order = wc_get_order($order_id);
        if (!$order || !$order->needs_payment()) {
            return 0;
        }

        return $order_id;
    }

    /**
     * Safety net when stock was reduced before payment completed (e.g. Germanized).
     *
     * @param bool $in_stock
     * @param \WC_Product $product
     */
    public static function treat_reserved_stock_as_in_stock($in_stock, $product)
    {
        if ($in_stock || !$product instanceof \WC_Product || !WC()->cart || WC()->cart->is_empty()) {
            return $in_stock;
        }

        $order_id = self::get_pending_order_id();
        if (!$order_id) {
            return $in_stock;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return $in_stock;
        }

        $product_id = (int) $product->get_stock_managed_by_id();
        if (!self::is_product_in_cart($product_id) || !self::is_product_in_order($order, $product_id)) {
            return $in_stock;
        }

        return true;
    }

    private static function is_product_in_cart(int $product_id): bool
    {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $cart_product = $cart_item['data'] ?? null;
            if ($cart_product instanceof \WC_Product && (int) $cart_product->get_stock_managed_by_id() === $product_id) {
                return true;
            }
        }

        return false;
    }

    private static function is_product_in_order(\WC_Order $order, int $product_id): bool
    {
        foreach ($order->get_items() as $item) {
            if (!$item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $line_product = $item->get_product();
            if ($line_product && (int) $line_product->get_stock_managed_by_id() === $product_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove interest fee from order after successful payment (if option enabled).
     *
     * @param \WC_Order $order
     */
    public function remove_interest_after_payment($order)
    {
        if ($this->plugin->get_option('remove_interest_after_payment') !== 'yes') {
            return;
        }

        if (!$this->plugin->is_easycredit_method($order->get_payment_method())) {
            return;
        }

        $interest_amount = $order->get_meta(Plugin::META_KEY_INTEREST_AMOUNT);
        if (!$interest_amount || $interest_amount <= 0) {
            return;
        }

        if (InterestFeeHandler::remove_from_order($order)) {
            $order->add_order_note(
                sprintf(
                    __('Interest amount of %s automatically removed from order total after successful payment.', 'wc-easycredit'),
                    wc_price($interest_amount, ['currency' => $order->get_currency()])
                )
            );
        }
    }
}
