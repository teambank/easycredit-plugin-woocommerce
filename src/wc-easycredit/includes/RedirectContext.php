<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit;

/**
 * Shared helpers for detecting the initial easyCredit redirect before financing approval.
 */
final class RedirectContext
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function isPendingEasycreditRedirect(?\WC_Order $order = null, array $postedData = []): bool
    {
        $payment_method = $this->resolvePaymentMethod($order, $postedData);

        if (!$payment_method || !$this->plugin->is_easycredit_method($payment_method)) {
            return false;
        }

        if ($this->isEasycreditRedirectSubmit($postedData)) {
            return true;
        }

        try {
            return !$this->plugin->integration()->checkout()->isApproved();
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function resolvePaymentMethod(?\WC_Order $order = null, array $postedData = []): ?string
    {
        if ($order instanceof \WC_Order) {
            $payment_method = $order->get_payment_method();

            return $payment_method !== '' ? $payment_method : null;
        }

        if (isset($postedData['payment_method']) && is_string($postedData['payment_method']) && $postedData['payment_method'] !== '') {
            return wc_clean($postedData['payment_method']);
        }

        if (isset($_POST['payment_method'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $payment_method = wc_clean(wp_unslash($_POST['payment_method'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ($payment_method !== '') {
                return $payment_method;
            }
        }

        $posted = $this->getPostedCheckoutData();
        if (isset($posted['payment_method']) && is_string($posted['payment_method']) && $posted['payment_method'] !== '') {
            return wc_clean($posted['payment_method']);
        }

        if (function_exists('WC') && WC()->session) {
            $chosen = WC()->session->get('chosen_payment_method');
            if (is_string($chosen) && $chosen !== '') {
                return $chosen;
            }
        }

        if (function_exists('WC') && WC()->checkout()) {
            $value = WC()->checkout()->get_value('payment_method');
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isEasycreditRedirectSubmit(array $postedData = []): bool
    {
        $sources = [$postedData, $this->getPostedCheckoutData()];

        if (isset($_POST['easycredit']) && is_array($_POST['easycredit'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $sources[] = wp_unslash($_POST['easycredit']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }

            $easycredit = $source['easycredit'] ?? $source;
            if (!is_array($easycredit)) {
                continue;
            }

            if (!empty($easycredit['submit'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPostedCheckoutData(): array
    {
        if (!isset($_POST['post_data'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return [];
        }

        $posted = [];

        if (is_string($_POST['post_data'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            parse_str(wp_unslash($_POST['post_data']), $posted); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif (is_array($_POST['post_data'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $posted = wp_unslash($_POST['post_data']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        return is_array($posted) ? $posted : [];
    }
}
