import { test, expect } from '@playwright/test';
import { randomize, takeScreenshot, scaleDown, delay } from "./utils";
import { goToProduct, goToCart, fillBlocksCheckout, goThroughPaymentPage, confirmOrder  } from './common';
import { PaymentTypes } from "./types";

test.beforeEach(scaleDown)
test.afterEach(takeScreenshot);

test.describe("go through blocks checkout @installment", () => {
	test('blocksCheckoutInstallments', async ({ page }) => {

	await goToProduct(page)

	await page.getByRole('button', { name: 'In den Warenkorb' }).click();
	await page.goto('index.php/checkout/')

	await fillBlocksCheckout(page);

	// Checkout Page
		await page
			.locator('easycredit-checkout-label[payment-type="INSTALLMENT"]')
			.click();
		await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zu easyCredit-Ratenkauf' }).click();

		await delay(500);
		await expect(
			page.locator(".wc-block-components-checkout-place-order-button")
		).not.toBeDisabled();

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});

test.describe("go through blocks checkout @bill", () => {
	test("blocksCheckoutBill", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillBlocksCheckout(page);

		// Checkout Page
		await page
			.locator('easycredit-checkout-label[payment-type="BILL"]')
			.click();

		await delay(2000);

		/* does not work inside the test ... :-(
	await page
		.locator('easycredit-checkout[payment-type="BILL"]')
		.getByRole("button", { name: "Weiter zum Rechnungskauf" })
		.click();
	*/
		await page
			.getByRole("button", { name: "Continue to pay by invoice" })
			.click();

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.BILL,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.BILL,
		});
	});
});

test.describe("go through @express blocks checkout @installment", () => {
	test("blocksExpressCheckoutInstallments", async ({ page }) => {
		await goToProduct(page);
		await page.getByRole("button", { name: "In den Warenkorb" }).click();

		await goToCart(page);

		await page
			.locator("a")
			.filter({ hasText: "Direkt in Raten" })
			.click();
		await page.getByText("Akzeptieren", { exact: true }).click();

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});

test.describe("go through @express blocks checkout @bill", () => {
	test("blocksExpressCheckoutBill", async ({ page }) => {
		await goToProduct(page);
		await page.getByRole("button", { name: "In den Warenkorb" }).click();

		await goToCart(page);

		await page
			.locator("a")
			.filter({ hasText: "In 30 Tagen zahlen" })
			.click();
		await page.getByText("Akzeptieren", { exact: true }).click();

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.BILL,
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.BILL,
		});
	});
});