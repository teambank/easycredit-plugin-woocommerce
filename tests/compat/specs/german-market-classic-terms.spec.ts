import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../../helpers/utils";
import {
	goToProduct,
	addCurrentProductToClassicCart,
	fillClassicCheckout,
	selectAndProceed,
	goThroughPaymentPage,
	acceptGermanMarketClassicLegalCheckboxes,
	expectGermanMarketClassicLegalCheckboxes,
	germanMarketClassicLegalCheckboxLocator,
	CLASSIC_CHECKOUT_PATH,
} from "../../helpers/common";
import { PaymentTypes } from "../../helpers/types";
import {
	ensureClassicCheckoutPage,
	isWpEnvCliAvailable,
	WP_ENV_CLI_SKIP_REASON,
} from "../../api/woocommerce-api";

/**
 * Manual-only compat tests for German Market on classic checkout.
 *
 * Run locally with:
 *   COMPAT_STACK=german-market pnpm test:compat:german-market
 */
test.beforeEach(scaleDown);
test.beforeEach(() => {
	test.skip(!isWpEnvCliAvailable(), WP_ENV_CLI_SKIP_REASON);
	ensureClassicCheckoutPage();
});
test.afterEach(takeScreenshot);

test.describe("German Market legal checkboxes defer classic @compat @compat-german-market @manual @installment @classic", () => {
	test("redirect proceeds without accepting legal checkboxes", async ({ page }) => {
		await goToProduct(page, "regular");
		await addCurrentProductToClassicCart(page);
		await fillClassicCheckout(page);

		const germanMarketCheckboxes = germanMarketClassicLegalCheckboxLocator(page);
		await expectGermanMarketClassicLegalCheckboxes(page);
		const germanMarketCount = await germanMarketCheckboxes.count();

		for (let i = 0; i < germanMarketCount; i++) {
			await expect(germanMarketCheckboxes.nth(i)).not.toBeChecked();
		}

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page).toHaveURL(/ratenkauf\.easycredit\.de/i, { timeout: 25_000 });
	});

	test("legal checkboxes are required after financing approval", async ({ page }) => {
		await goToProduct(page, "regular");
		await addCurrentProductToClassicCart(page);
		await fillClassicCheckout(page);

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await page.goto(CLASSIC_CHECKOUT_PATH);

		await expect(page.locator("easycredit-checkout")).toHaveAttribute("payment-plan");

		const placeOrderButton = page.getByRole("button", {
			name: /jetzt kaufen|pflichtig bestellen|Bestellung aufgeben/i,
		});
		await placeOrderButton.click();

		await expect(page).not.toHaveURL(/order-received/);

		await acceptGermanMarketClassicLegalCheckboxes(page);

		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
});
