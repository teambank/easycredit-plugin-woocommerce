/**
 * Third-party compatibility: WooCommerce Germanized legal checkboxes.
 *
 * Verified in CI with Germanized 4.0.9 on WooCommerce 10.9.2 (blocks checkout).
 */
export const isGermanizedLegalValidationErrorKey = (key) =>
	key.startsWith("checkbox-");

export const GERMANIZED_LEGAL_CHECKBOX_SELECTORS = [
	".wp-block-woocommerce-germanized-checkout-checkboxes input[type='checkbox']",
	".wc-gzd-checkboxes input[type='checkbox']",
].join(", ");
