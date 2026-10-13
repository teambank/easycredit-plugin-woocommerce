export {
	PAYMENT_API_HOST,
	PAYMENT_SANDBOX,
	shouldUsePaymentApi,
	resolvePaymentPageMode,
	extractTechnicalTransactionId,
	goThroughPaymentPageViaApi,
} from "easycredit-playwright-payment";

/** Must stay below the Playwright test timeout (60s) so failures surface in-step. */
export const PAYMENT_REDIRECT_TIMEOUT_MS = 25_000;

const PAYMENT_URL_PATTERN = /ratenkauf\.easycredit\.de/i;

export const NATIVE_TERMS_VALIDATION_PATTERN =
	/Allgemeinen Geschäftsbedingungen|Geschäftsbedingungen|terms and conditions|Bitte lies und akzeptiere/i;

export const blocksNativeTermsFailureLocator = (page: any) => {
	const termsBlock = page.locator(".wp-block-woocommerce-checkout-terms-block");

	// WC 10+ marks the checkbox wrapper with .has-error. WC 11 also renders the
	// validation alert, so both can match at once. Prefer the wrapper and take
	// the first match so Playwright strict mode still sees one element.
	return termsBlock
		.locator(".wc-block-components-checkbox.has-error")
		.or(
			termsBlock
				.locator(".wc-block-components-validation-error")
				.filter({ hasText: NATIVE_TERMS_VALIDATION_PATTERN }),
		)
		.first();
};

const CHECKOUT_FAILURE_PATTERNS: Array<{ pattern: RegExp; message: string }> = [
	{
		pattern: /ist ausverkauft und kann nicht gekauft werden/i,
		message: "Product is sold out on checkout",
	},
	{
		pattern: /No event handler handled the submit event/i,
		message: "easyCredit checkout widget submit was not handled",
	},
];

const TERMS_CHECKOUT_FAILURE_MESSAGE =
	"Terms and conditions validation blocked checkout";

const CHECKOUT_FAILURE_LOCATOR_TIMEOUT_MS = 500;

function isShopCheckoutPage(page: any): boolean {
	if (PAYMENT_URL_PATTERN.test(page.url())) {
		return false;
	}

	const baseURL = process.env.BASE_URL ?? "http://localhost/";
	try {
		return new URL(page.url()).origin === new URL(baseURL).origin;
	} catch {
		return false;
	}
}

async function collectNativeTermsCheckoutFailure(
	page: any,
	{ includeVisualState = false }: { includeVisualState?: boolean } = {},
): Promise<string | null> {
	if (!isShopCheckoutPage(page)) {
		return null;
	}

	const wcErrors = await page
		.locator(".woocommerce-error li")
		.allTextContents({ timeout: CHECKOUT_FAILURE_LOCATOR_TIMEOUT_MS })
		.catch(() => []);
	if (
		wcErrors.some((text) => NATIVE_TERMS_VALIDATION_PATTERN.test(text))
	) {
		return TERMS_CHECKOUT_FAILURE_MESSAGE;
	}

	const blocksTermsLocator = page.locator(
		".wp-block-woocommerce-checkout-terms-block .wc-block-components-validation-error",
	);
	if ((await blocksTermsLocator.count()) > 0) {
		const blocksTermsError = await blocksTermsLocator
			.first()
			.innerText({ timeout: CHECKOUT_FAILURE_LOCATOR_TIMEOUT_MS })
			.catch(() => "");
		if (
			blocksTermsError &&
			NATIVE_TERMS_VALIDATION_PATTERN.test(blocksTermsError)
		) {
			return TERMS_CHECKOUT_FAILURE_MESSAGE;
		}
	}

	// WC 10+ shows .has-error / aria-invalid while terms are unchecked. That state also
	// appears briefly during the deferred easyCredit redirect submit, so only treat it as
	// a hard failure once redirect wait has exhausted (includeVisualState).
	if (!includeVisualState) {
		return null;
	}

	const termsBlock = page.locator(".wp-block-woocommerce-checkout-terms-block");
	if (
		(await termsBlock
			.locator(
				".wc-block-components-checkbox.has-error, #terms-and-conditions[aria-invalid='true']",
			)
			.count()) > 0
	) {
		return TERMS_CHECKOUT_FAILURE_MESSAGE;
	}

	return null;
}

async function collectCheckoutFailureReason(
	page: any,
	{ includeVisualTermsState = false }: { includeVisualTermsState?: boolean } = {},
): Promise<string | null> {
	if (!isShopCheckoutPage(page)) {
		return null;
	}

	const termsFailure = await collectNativeTermsCheckoutFailure(page, {
		includeVisualState: includeVisualTermsState,
	});
	if (termsFailure) {
		return termsFailure;
	}

	const bodyText = await page
		.locator("body")
		.innerText({ timeout: CHECKOUT_FAILURE_LOCATOR_TIMEOUT_MS })
		.catch(() => "");
	for (const { pattern, message } of CHECKOUT_FAILURE_PATTERNS) {
		if (pattern.test(bodyText)) {
			return message;
		}
	}

	const wcErrors = await page
		.locator(".woocommerce-error li")
		.allTextContents({ timeout: CHECKOUT_FAILURE_LOCATOR_TIMEOUT_MS })
		.catch(() => []);
	if (wcErrors.length > 0) {
		return `WooCommerce checkout errors: ${wcErrors.join("; ")}`;
	}

	return null;
}

export async function waitForPaymentRedirect(
	page: any,
	timeout = PAYMENT_REDIRECT_TIMEOUT_MS
): Promise<void> {
	const deadline = Date.now() + timeout;

	while (Date.now() < deadline) {
		if (PAYMENT_URL_PATTERN.test(page.url())) {
			return;
		}

		if (isShopCheckoutPage(page)) {
			const failureReason = await collectCheckoutFailureReason(page, {
				includeVisualTermsState: false,
			});
			if (failureReason) {
				throw new Error(
					`Payment redirect failed (${failureReason}). URL: ${page.url()}`
				);
			}
		}

		await page.waitForTimeout(250);
	}

	if (PAYMENT_URL_PATTERN.test(page.url())) {
		return;
	}

	const failureReason = isShopCheckoutPage(page)
		? await collectCheckoutFailureReason(page, {
				includeVisualTermsState: true,
			})
		: null;
	throw new Error(
		`Timed out after ${timeout}ms waiting for redirect to easyCredit payment page. URL: ${page.url()}${
			failureReason ? `. ${failureReason}` : ""
		}`
	);
}
