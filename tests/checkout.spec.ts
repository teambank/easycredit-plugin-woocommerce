import { test, expect } from '@playwright/test';
import { randomize, takeScreenshot, scaleDown } from "./utils";
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

test('blocksCheckoutInstallments', async ({ page }) => {

  await goToProduct(page)

  await page.getByRole('button', { name: 'In den Warenkorb' }).click();
  await page.goto('index.php/checkout/')

  await fillCheckout(page);

  await expect(page.locator('.wc-block-components-checkout-place-order-button')).not.toBeDisabled();

  // Checkout Page
	await page
		.locator('easycredit-checkout-label[payment-type="INSTALLMENT"]')
		.click();
  await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zum Ratenkauf' }).click();
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

test("blocksCheckoutBill", async ({ page }) => {

	await goToProduct(page);

	await page.getByRole("button", { name: "In den Warenkorb" }).click();
	await page.goto("index.php/checkout/");

    await fillCheckout(page)

	await expect(
		page.locator(".wc-block-components-checkout-place-order-button")
	).not.toBeDisabled();

	// Checkout Page
	await page
		.locator('easycredit-checkout-label[payment-type="BILL"]')
		.click();
	await page
		.locator("easycredit-checkout")
		.getByRole("button", { name: "Weiter zum Rechnungskauf" })
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

test("blocksExpressCheckoutInstallments", async ({ page }) => {
	await goToProduct(page);
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

test("blocksExpressCheckoutBill", async ({ page }) => {
	await goToProduct(page);
	await goToCart(page);

	await page.locator("a").filter({ hasText: "In 30 Tagen zahlen" }).click();
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