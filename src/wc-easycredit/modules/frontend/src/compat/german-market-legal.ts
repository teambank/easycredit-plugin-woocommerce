/**
 * Third-party compatibility: WooCommerce German Market legal checkboxes (classic checkout).
 *
 * Manually verified with German Market 3.60 on WooCommerce 10.9.2.
 */
export const GERMAN_MARKET_CLASSIC_LEGAL_FIELD_NAMES = [
	"shipping-service-provider",
	"widerruf-digital-acknowledgement",
	"age-rating",
	"german-market-custom-checkbox",
] as const;

export const DEFERRED_LEGAL_CONTAINER_SELECTORS =
	"p.legal, .wc-gzd-checkbox, .wc-gzd-checkboxes, [data-checkbox], .german-market-checkbox-p";

export const DEFERRED_LEGAL_CHECKBOX_SELECTORS = [
	'input[name="legal"]',
	'input[name="terms"]',
	...GERMAN_MARKET_CLASSIC_LEGAL_FIELD_NAMES.map(
		(name) => `input[name="${name}"]`,
	),
	"p.legal input[type='checkbox']",
	".wc-gzd-checkbox input[type='checkbox']",
	".wc-gzd-checkboxes input[type='checkbox']",
	"[data-checkbox] input[type='checkbox']",
	".german-market-checkbox-p input[type='checkbox']",
].join(", ");

export const isGermanMarketClassicLegalInput = ($input: JQuery): boolean => {
	if (!$input.length) {
		return false;
	}

	const name = $input.attr("name") ?? "";
	if (
		GERMAN_MARKET_CLASSIC_LEGAL_FIELD_NAMES.includes(
			name as (typeof GERMAN_MARKET_CLASSIC_LEGAL_FIELD_NAMES)[number],
		)
	) {
		return true;
	}

	return $input.closest(".german-market-checkbox-p").length > 0;
};
