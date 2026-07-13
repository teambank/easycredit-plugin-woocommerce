import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../../helpers/utils";
import {
	goToProduct,
	addCurrentProductToCart,
	selectAndProceed,
	goThroughPaymentPage,
	acceptEasycreditPrivacyModal,
	acceptGermanMarketLegalCheckboxes,
	expectGermanMarketLegalCheckboxes,
	germanMarketLegalCheckboxLocator,
	blocksPlaceOrderButtonLocator,
} from "../../helpers/common";
import { fillGermanMarketBlocksCheckout } from "../helpers/german-market";
import { PaymentTypes } from "../../helpers/types";

/**
 * Manual-only compat tests for the commercial German Market plugin.
 *
 * Run locally with:
 *   COMPAT_STACK=german-market pnpm test:compat:german-market
 */
test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("German Market legal checkboxes defer @compat @compat-german-market @manual @installment", () => {
	test("redirect proceeds without accepting legal checkboxes", async ({ page }) => {
		await goToProduct(page, "regular");
		await addCurrentProductToCart(page);
		await fillGermanMarketBlocksCheckout(page);

		const germanMarketCheckboxes = germanMarketLegalCheckboxLocator(page);
		await expectGermanMarketLegalCheckboxes(page);
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
		await addCurrentProductToCart(page);
		await fillGermanMarketBlocksCheckout(page);

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("easycredit-checkout")).toHaveAttribute("payment-plan");

		const placeOrderButton = blocksPlaceOrderButtonLocator(page);
		await placeOrderButton.click();
		await acceptEasycreditPrivacyModal(page);

		await expect(page).not.toHaveURL(/order-received/);

		await acceptGermanMarketLegalCheckboxes(page);

		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
});
