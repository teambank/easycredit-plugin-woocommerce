<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\RedirectContext;
use Netzkollektiv\EasyCredit\Plugin;

/**
 * Defer WooCommerce's native terms checkbox until easyCredit financing is approved.
 */
class NativeTermsCompatibility
{
    private RedirectContext $context;

    public function __construct(Plugin $plugin)
    {
        $this->context = new RedirectContext($plugin);

        add_action('woocommerce_after_checkout_validation', [$this, 'deferTermsValidation'], 2, 2);
    }

    /**
     * @param array<string, mixed> $data
     * @param \WP_Error $errors
     */
    public function deferTermsValidation($data, $errors): void
    {
        if (!$errors instanceof \WP_Error || !$this->shouldDeferNativeTerms(is_array($data) ? $data : [])) {
            return;
        }

        if ($errors->get_error_code('terms')) {
            $errors->remove('terms');
        }
    }

    private function shouldDeferNativeTerms(array $data = []): bool
    {
        if (!function_exists('wc_terms_and_conditions_checkbox_enabled') || !wc_terms_and_conditions_checkbox_enabled()) {
            return false;
        }

        if (!apply_filters('woocommerce_checkout_show_terms', true)) {
            return false;
        }

        return $this->context->isPendingEasycreditRedirect(null, $data);
    }
}
