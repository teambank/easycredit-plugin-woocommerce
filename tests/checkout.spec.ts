import { test, expect } from '@playwright/test';
import { randomize, takeScreenshot, scaleDown, delay } from "./utils";
import { goToProduct, goToCart, goThroughPaymentPage, confirmOrder  } from './common';
import { PaymentTypes } from "./types";

test.beforeEach(scaleDown)
test.afterEach(takeScreenshot);

const fillCheckout = async (page) => {
	await page.getByLabel("E-Mail-Adresse").fill("ralf.ratenkauf@teambank.de");
	await page
		.getByRole("textbox", { name: "Vorname" })
		.fill(randomize("Ralf"));
	await page.getByRole("textbox", { name: "Nachname" }).fill("Ratenkauf");
	await page
		.getByRole("textbox", { name: "Adresse", exact: true })
		.fill("Beuthener Str. 25");
	await page.getByRole("textbox", { name: "Postleitzahl" }).fill("90471");
	await page.getByRole("textbox", { name: "Stadt" }).fill("Nürnberg");
	await page
		.getByRole("textbox", { name: "Telefon (optional)" })
		.fill("012345678");
};

test.describe('Go through blocks checkout (INSTALLMENT)', () => {
	test('blocksCheckoutInstallments', async ({ page }) => {

	await goToProduct(page)

	await page.getByRole('button', { name: 'In den Warenkorb' }).click();
	await page.goto('index.php/checkout/')

	await fillCheckout(page);

	// Checkout Page
		await page
			.locator('easycredit-checkout-label[payment-type="INSTALLMENT"]')
			.click();
		await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zum Ratenkauf' }).click();

		await delay(500);
		await expect(
			page.locator(".wc-block-components-checkout-place-order-button")
		).not.toBeDisabled();

		await page.locator('span:text("Akzeptieren"):visible').click();

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

test.describe("Go through blocks checkout (BILL)", () => {
	test("blocksCheckoutBill", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillCheckout(page);

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

test.describe("Go through express blocks checkout (INSTALLMENT)", () => {
	test("blocksExpressCheckoutInstallments", async ({ page }) => {
		await goToProduct(page);
		await page.getByRole("button", { name: "In den Warenkorb" }).click();

		await goToCart(page);

		await page
			.locator("a")
			.filter({ hasText: "Jetzt direkt in Raten zahlen" })
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

test.describe("Go through express blocks checkout (BILL)", () => {
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