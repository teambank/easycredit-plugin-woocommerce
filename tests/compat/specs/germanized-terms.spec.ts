import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../../helpers/utils";
import {
	goToProduct,
	addCurrentProductToCart,
	fillBlocksCheckout,
	selectAndProceed,
	goThroughPaymentPage,
	confirmOrder,
	acceptEasycreditPrivacyModal,
	acceptGermanizedLegalCheckboxes,
	acceptNativeTermsCheckbox,
	expectGermanizedLegalCheckboxes,
	germanizedLegalCheckboxLocator,
	blocksPlaceOrderButtonLocator,
} from "../../helpers/common";
import { PaymentTypes } from "../../helpers/types";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Germanized legal checkboxes defer @compat @compat-germanized @installment", () => {
	test("redirect proceeds without accepting legal checkboxes", async ({ page }) => {
		await goToProduct(page, "regular");
		await addCurrentProductToCart(page);
		await fillBlocksCheckout(page);

		const germanizedCheckboxes = germanizedLegalCheckboxLocator(page);
		await expectGermanizedLegalCheckboxes(page);
		const germanizedCount = await germanizedCheckboxes.count();

		for (let i = 0; i < germanizedCount; i++) {
			await expect(germanizedCheckboxes.nth(i)).not.toBeChecked();
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
		await fillBlocksCheckout(page);

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

		await acceptGermanizedLegalCheckboxes(page);
		await acceptNativeTermsCheckbox(page);

		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
});
