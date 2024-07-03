import { test, expect } from "@playwright/test";
import { randomize, takeScreenshot, scaleDown } from "./utils";
import { goToProduct, goThroughPaymentPage, confirmOrder } from "./common";
import { PaymentTypes } from "./types";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

const fillCheckout = async (page) => {
	await page
		.getByRole("textbox", { name: "Vorname *" })
		.fill(randomize("Ralf"));
	await page.getByRole("textbox", { name: "Nachname *" }).fill("Ratenkauf");
	await page
		.getByRole("textbox", { name: "Straße *" })
		.fill("Beuthener Str. 25");
	await page.getByRole("textbox", { name: "Postleitzahl *" }).fill("90471");
	await page.getByRole("textbox", { name: "Ort / Stadt *" }).fill("Nürnberg");
	await page.getByRole("textbox", { name: "Telefon *" }).fill("012345678");
	await page
		.getByLabel("E-Mail-Adresse *")
		.fill("ralf.ratenkauf@teambank.de");
}

test("standardCheckoutInstallments", async ({ page }) => {

	await goToProduct(page);

	await page.getByRole("button", { name: "In den Warenkorb" }).click();
	await page.goto("index.php/checkout/");

    await fillCheckout(page)

	/* Confirm Page */
	await page.locator('easycredit-checkout-label[payment-type="INSTALLMENT"]').click();
	await page
		.locator("easycredit-checkout")
		.getByRole("button", { name: "Weiter zum Ratenkauf" })
		.click();
	await page.locator('span:text("Akzeptieren"):visible').click();

	await goThroughPaymentPage({ page: page, paymentType: PaymentTypes.INSTALLMENT });
	await confirmOrder({
		page: page,
		paymentType: PaymentTypes.INSTALLMENT,
	});
});

test("standardCheckoutBill", async ({ page }) => {
	await goToProduct(page);

	await page.getByRole("button", { name: "In den Warenkorb" }).click();
	await page.goto("index.php/checkout/");

	await fillCheckout(page);

	/* Confirm Page */
	await page
		.locator('easycredit-checkout-label[payment-type="BILL"]')
		.click();
	await page
		.locator("easycredit-checkout")
		.getByRole("button", { name: "Weiter zum Rechnungskauf" })
		.click();

	await goThroughPaymentPage({page: page, paymentType: PaymentTypes.BILL });
	await confirmOrder({
		page: page,
		paymentType: PaymentTypes.BILL,
	});
});

test("expressCheckoutInstallments", async ({ page }) => {
	await goToProduct(page);

	await page
		.locator("a")
		.filter({ hasText: "Jetzt direkt in Raten zahlen" })
		.click();
	await page.getByText("Akzeptieren", { exact: true }).click();

	await goThroughPaymentPage({
		page: page,
		paymentType: PaymentTypes.INSTALLMENT,
		express: true
	});
	await confirmOrder({
		page: page,
		paymentType: PaymentTypes.INSTALLMENT,
	});
});

test("expressCheckoutBill", async ({ page }) => {
	await goToProduct(page);

	await page
		.locator("a")
		.filter({ hasText: "In 30 Tagen zahlen" })
		.click();
	await page.getByText("Akzeptieren", { exact: true }).click();

	await goThroughPaymentPage({
		page: page,
		paymentType: PaymentTypes.BILL,
		express: true
	});	
	await confirmOrder({
		page: page,
		paymentType: PaymentTypes.BILL
	});
});

test("expressCheckoutWithVariableProductInstallment", async ({ page }) => {
	await goToProduct(page, "variable");

	await page.getByLabel("Size").selectOption("");
	await expect(page.locator("easycredit-express-button")).not.toBeVisible();

	await page.getByLabel("Size").selectOption("medium");
	await expect(page.locator("easycredit-express-button")).not.toBeVisible();

	await page.getByLabel("Size").selectOption("small");
	await expect(page.locator("easycredit-express-button")).toBeVisible();

	await page
		.locator("a")
		.filter({ hasText: "Jetzt direkt in Raten zahlen" })
		.click();
	await page.getByText("Akzeptieren", { exact: true }).click();

	await goThroughPaymentPage({
		page: page,
		paymentType: PaymentTypes.INSTALLMENT,
		express: true
	});
	await confirmOrder({
		page: page,
		paymentType: PaymentTypes.INSTALLMENT,
	});
});