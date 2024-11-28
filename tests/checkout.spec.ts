import { test } from '@playwright/test';
import { takeScreenshot, scaleDown } from "./utils";
import { goToProduct, goToCart, fillBlocksCheckout, selectAndProceed, goThroughPaymentPage, confirmOrder  } from './common';
import { PaymentTypes } from "./types";

test.beforeEach(scaleDown)
test.afterEach(takeScreenshot);

test.describe("go through blocks checkout @installment", () => {
	test('blocksCheckoutInstallments', async ({ page }) => {

		await goToProduct(page)

		await page.getByRole('button', { name: 'In den Warenkorb' }).click();
		await page.goto('index.php/checkout/')

		await fillBlocksCheckout(page);

		await selectAndProceed({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,			
		});

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

		await selectAndProceed({
			page: page,
			paymentType: PaymentTypes.BILL,
		});

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
			.first()
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