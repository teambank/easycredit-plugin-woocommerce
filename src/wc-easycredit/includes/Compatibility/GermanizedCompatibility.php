<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\RedirectContext;
use Netzkollektiv\EasyCredit\Plugin;

/**
 * Germanized compatibility for easyCredit's two-step checkout.
 *
 * Defers mandatory Germanized legal checkboxes during the initial financing redirect
 * and requires real checkbox confirmation after the customer returns with a plan.
 *
 * Also delays Germanized instant order confirmation (and the stock reduction tied to it)
 * until payment_complete, then ensures the confirmation mails are sent.
 *
 * Verified in CI with:
 * - WordPress 7.0
 * - WooCommerce 10.9.2
 * - Germanized 4.0.9
 */
class GermanizedCompatibility
{
    private Plugin $plugin;

    private RedirectContext $redirectContext;

    /**
     * @var list<string>
     */
    private const CHECKBOX_META_KEYS = [
        '_parcel_delivery_opted_in',
        '_photovoltaic_systems_opted_in',
        '_min_age',
    ];

    /**
     * @var list<int>
     */
    private array $pendingConfirmationOrderIds = [];

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->redirectContext = new RedirectContext($plugin);

        add_filter('woocommerce_gzd_instant_order_confirmation', [$this, 'delay_until_payment_complete'], 10, 2);
        add_filter('woocommerce_germanized_send_instant_order_confirmation', [$this, 'delay_until_payment_complete'], 10, 2);
        add_action('woocommerce_payment_complete', [$this, 'maybe_send_delayed_confirmation'], 100);

        add_action('woocommerce_gzd_run_legal_checkboxes_checkout', [$this, 'maybeRelaxCheckboxValidation'], 5);
        add_action('woocommerce_after_checkout_validation', [$this, 'relaxCheckboxValidationForClassicCheckout'], 0, 2);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'relaxBlocksCheckboxValidation'], 5, 2);

        add_action('woocommerce_checkout_order_created', [$this, 'clearPrematureCheckboxData'], 25);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'clearPrematureCheckboxDataFromBlocks'], 25, 2);
    }

    /**
     * Hold back Germanized's instant confirmation while the order still needs payment.
     *
     * Re-reads the order from the datastore so a stale Blocks PaymentContext object
     * (still pending in memory after payment_complete on another instance) does not
     * keep suppressing New Order / Bestellbestätigung forever.
     *
     * @param bool $send_confirmation
     * @param \WC_Order|int|null $order
     */
    public function delay_until_payment_complete($send_confirmation, $order = null)
    {
        if (!$send_confirmation) {
            return $send_confirmation;
        }

        $order = $this->resolve_order($order);
        if (!$order || !$order->needs_payment()) {
            return $send_confirmation;
        }

        if ($this->plugin->is_easycredit_method($order->get_payment_method())) {
            return false;
        }

        return $send_confirmation;
    }

    /**
     * After easyCredit payment_complete, ensure Germanized confirmation mails go out.
     *
     * Germanized suppresses the status-transition copies of Neue Bestellung and
     * Bestellbestätigung once the order is paid, and expects its own confirmation
     * instead. WooCommerce 11 queues that action and only dispatches the queue on
     * shutdown at priority 100, so a later PHP shutdown misses it. Run just before
     * that dispatch and trigger the mails directly.
     *
     * @param int $order_id
     */
    public function maybe_send_delayed_confirmation($order_id): void
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof \WC_Order) {
            return;
        }

        if (!$this->plugin->is_easycredit_method($order->get_payment_method())) {
            return;
        }

        $this->pendingConfirmationOrderIds[] = $order_id;

        if (!has_action('shutdown', [$this, 'send_pending_confirmations'])) {
            add_action('shutdown', [$this, 'send_pending_confirmations'], 20);
        }
    }

    public function send_pending_confirmations(): void
    {
        $order_ids = $this->pendingConfirmationOrderIds;
        $this->pendingConfirmationOrderIds = [];

        foreach ($order_ids as $order_id) {
            $this->send_delayed_confirmation_if_needed($order_id);
        }
    }

    /**
     * @param int $order_id
     */
    public function send_delayed_confirmation_if_needed($order_id): void
    {
        if (did_action('woocommerce_germanized_order_confirmation_sent')) {
            return;
        }

        if (!function_exists('WC_germanized')) {
            return;
        }

        $emails = WC_germanized()->emails ?? null;
        if (!$emails || !is_callable([$emails, 'trigger_order_confirmation_emails'])) {
            return;
        }

        $order = wc_get_order((int) $order_id);
        if (!$order instanceof \WC_Order) {
            return;
        }

        if (!$this->plugin->is_easycredit_method($order->get_payment_method())) {
            return;
        }

        if ($order->needs_payment()) {
            return;
        }

        $emails->trigger_order_confirmation_emails($order, $order->get_id());
    }

    /**
     * Classic checkout validates via woocommerce_after_checkout_validation and does not
     * re-run the render hooks on every request the way blocks checkout does.
     *
     * @param array $data
     * @param \WP_Error $errors
     */
    public function relaxCheckboxValidationForClassicCheckout($data, $errors): void
    {
        unset($data, $errors);

        $this->relaxCheckboxValidation();
    }

    /**
     * @param \WC_GZD_Legal_Checkbox_Manager $manager
     */
    public function maybeRelaxCheckboxValidation($manager): void
    {
        unset($manager);

        $this->relaxCheckboxValidation(null);
    }

    /**
     * Blocks checkout validates Germanized checkboxes via the Store API, not the
     * classic woocommerce_gzd_run_legal_checkboxes_checkout hook.
     *
     * @param \WC_Order $order
     * @param \WP_REST_Request $request
     */
    public function relaxBlocksCheckboxValidation($order, $request): void
    {
        unset($request);

        if (!$order instanceof \WC_Order) {
            return;
        }

        $this->relaxCheckboxValidation($order);
    }

    private function relaxCheckboxValidation(?\WC_Order $order = null): void
    {
        if (!$this->isPendingEasycreditRedirect($order)) {
            return;
        }

        $manager = \WC_GZD_Legal_Checkbox_Manager::instance();

        foreach ($manager->get_checkboxes(['locations' => 'checkout'], 'render') as $checkbox) {
            $checkbox->set_is_mandatory(false);
        }
    }

    /**
     * @param \WC_Order $order
     */
    public function clearPrematureCheckboxData($order): void
    {
        if (!$order instanceof \WC_Order || !$this->isPendingEasycreditRedirect($order)) {
            return;
        }

        $this->removeCheckboxMeta($order);
        $this->resetCheckboxSession();
    }

    /**
     * @param \WC_Order $order
     * @param \WP_REST_Request $request
     */
    public function clearPrematureCheckboxDataFromBlocks($order, $request): void
    {
        unset($request);

        $this->clearPrematureCheckboxData($order);
    }

    private function isPendingEasycreditRedirect(?\WC_Order $order = null): bool
    {
        return $this->redirectContext->isPendingEasycreditRedirect($order);
    }

    private function removeCheckboxMeta(\WC_Order $order): void
    {
        $changed = false;

        foreach (self::CHECKBOX_META_KEYS as $meta_key) {
            if ($order->meta_exists($meta_key)) {
                $order->delete_meta_data($meta_key);
                $changed = true;
            }
        }

        if ($changed) {
            $order->save();
        }
    }

    private function resetCheckboxSession(): void
    {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        WC()->session->set('checkout_checkboxes_checked', []);
    }

    /**
     * @param \WC_Order|int|null $order
     */
    private function resolve_order($order): ?\WC_Order
    {
        $order_id = 0;

        if ($order instanceof \WC_Order) {
            $order_id = (int) $order->get_id();
        } elseif (is_numeric($order)) {
            $order_id = (int) $order;
        }

        if ($order_id <= 0) {
            return $order instanceof \WC_Order ? $order : null;
        }

        // Always re-read: Blocks keeps a pre-payment order instance in PaymentContext
        // while process_payment() calls payment_complete() on a different instance.
        $fresh = wc_get_order($order_id);

        return $fresh instanceof \WC_Order ? $fresh : null;
    }
}
