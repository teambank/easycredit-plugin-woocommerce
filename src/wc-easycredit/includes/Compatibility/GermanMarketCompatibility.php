<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\RedirectContext;
use Netzkollektiv\EasyCredit\Plugin;

/**
 * German Market compatibility for easyCredit's two-step checkout.
 *
 * Defers mandatory German Market legal checkboxes during the initial financing redirect
 * and requires real checkbox confirmation after the customer returns with a plan.
 *
 * Manually verified with:
 * - WordPress 7.0
 * - WooCommerce 10.9.2
 * - German Market 3.60
 */
class GermanMarketCompatibility
{
    private RedirectContext $redirectContext;

    /**
     * @var list<string>
     */
    private const CHECKOUT_ERROR_CODES = [
        'terms',
        'german_market_checkbox_2_digital_content',
        'german_market_age_rating',
        'german_market_checkbox_3_shipping_service_provider',
        'german_market_checkbox_4_custom',
    ];

    public function __construct(Plugin $plugin)
    {
        $this->redirectContext = new RedirectContext($plugin);

        add_filter(
            'german_market_checkout_after_validation_without_sec_checkout_return',
            [$this, 'skipCheckboxValidationDuringRedirect'],
            1,
            4
        );
        add_filter('german_market_checkout_checkbox_is_required', [$this, 'relaxCheckboxRequirement'], 10, 4);
        add_filter(
            'german_market_checkbox_3_shipping_service_provider_validation',
            [$this, 'skipShippingCheckboxValidationDuringRedirect'],
            10,
            1
        );
        add_filter('gm_checkout_validation_first_checkout', [$this, 'relaxFirstCheckoutValidation'], 1);
        add_filter('gm_checkout_validation_fields', [$this, 'relaxAdditionalValidation'], 1, 3);
        add_action('woocommerce_after_checkout_validation', [$this, 'relaxClassicCheckboxValidation'], 15, 2);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'clearPrematureCheckboxDataFromBlocks'], 1, 2);
    }

    /**
     * @param bool $skip_validation
     * @param array<string, mixed> $data
     * @param \WP_Error $errors
     * @param array<string, mixed> $request_data
     */
    public function skipCheckboxValidationDuringRedirect($skip_validation, $data, $errors, $request_data): bool
    {
        unset($errors);

        if ($skip_validation) {
            return true;
        }

        return $this->isPendingEasycreditRedirect(
            null,
            $this->mergePostedData(is_array($data) ? $data : [], is_array($request_data) ? $request_data : [])
        );
    }

    public function skipShippingCheckboxValidationDuringRedirect($validate): bool
    {
        if (!$validate) {
            return false;
        }

        return !$this->isPendingEasycreditRedirect();
    }

    /**
     * @param bool $required
     * @param string $field_name
     * @param bool $checkout_validated
     * @param array<string, mixed> $post_data
     */
    public function relaxCheckboxRequirement($required, $field_name, $checkout_validated, $post_data): bool
    {
        unset($field_name, $checkout_validated);

        if (!$required) {
            return false;
        }

        return !$this->isPendingEasycreditRedirect(
            null,
            is_array($post_data) ? $post_data : []
        );
    }

    /**
     * @param int $error_count
     */
    public function relaxFirstCheckoutValidation($error_count): int
    {
        if ($error_count === 0 || !$this->isPendingEasycreditRedirect()) {
            return (int) $error_count;
        }

        return 0;
    }

    /**
     * @param int $error_count
     * @param \WP_Error $errors
     * @param array<string, mixed> $data
     */
    public function relaxAdditionalValidation($error_count, $errors, $data): int
    {
        unset($errors);

        if ((int) $error_count === 0 || !$this->isPendingEasycreditRedirect(
            null,
            is_array($data) ? $data : []
        )) {
            return (int) $error_count;
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $data
     * @param \WP_Error $errors
     */
    public function relaxClassicCheckboxValidation($data, $errors): void
    {
        if (!$errors instanceof \WP_Error || !$this->isPendingEasycreditRedirect(
            null,
            $this->mergePostedData(is_array($data) ? $data : [])
        )) {
            return;
        }

        foreach (self::CHECKOUT_ERROR_CODES as $error_code) {
            $errors->remove($error_code);
        }
    }

    /**
     * @param \WC_Order $order
     * @param \WP_REST_Request $request
     */
    public function clearPrematureCheckboxDataFromBlocks($order, $request): void
    {
        if (
            !$order instanceof \WC_Order
            || !$request instanceof \WP_REST_Request
            || !$this->isPendingEasycreditRedirect($order)
        ) {
            return;
        }

        $extensions = $request->get_param('extensions');
        if (!is_array($extensions) || !isset($extensions['german-market-store-api-integration'])) {
            return;
        }

        unset($extensions['german-market-store-api-integration']);
        $request->set_param('extensions', $extensions);
    }

    /**
     * @param array<string, mixed> $postedData
     */
    private function isPendingEasycreditRedirect(?\WC_Order $order = null, array $postedData = []): bool
    {
        return $this->redirectContext->isPendingEasycreditRedirect($order, $postedData);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $requestData
     *
     * @return array<string, mixed>
     */
    private function mergePostedData(array $data = [], array $requestData = []): array
    {
        $posted = array_merge($this->redirectContext->getPostedCheckoutData(), $data, $requestData);

        return is_array($posted) ? $posted : [];
    }
}
