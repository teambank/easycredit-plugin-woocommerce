/**
 * Third-party compatibility: WooCommerce German Market legal checkboxes.
 *
 * Manually verified with German Market 3.60 on WooCommerce 10.9.2 (blocks checkout).
 *
 */
export const isGermanMarketLegalValidationErrorKey = (key) =>
	key.startsWith("german-market-checkbox-");

export const GERMAN_MARKET_LEGAL_CHECKBOX_SELECTORS =
	".german-market-block-checkout-checkboxes input[type='checkbox']";
