import { test, expect } from "@playwright/test";
import {
	takeScreenshot,
	scaleDown,
	delay,
	greaterOrEqualsThan,
	doWithRetry,
} from "../helpers/utils";
import {
	goToProduct,
	goToCart,
	fillBlocksCheckout,
	selectAndProceed,
	goThroughPaymentPage,
	startExpress,
	confirmOrder,
	addCurrentProductToCart,
	checkAddressInvalidation,
	checkAmountInvalidation,
} from "../helpers/common";
import { PaymentTypes } from "../helpers/types";
import { setProductStock } from "../api/woocommerce-api";

const LAST_STOCK_SKU = "lastone";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("go through blocks checkout @installment", () => {
	test("blocksCheckoutInstallments", async ({ page }) => {
		await goToProduct(page);

		await addCurrentProductToCart(page);

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

		await addCurrentProductToCart(page);

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
		await page.getByRole("button", { name: "In den Warenkorb" }).first().click();

		await goToCart(page);
		await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

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
		await page.getByRole("button", { name: "In den Warenkorb" }).first().click();

		await goToCart(page);

		await startExpress({ page, paymentType: PaymentTypes.BILL });

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

test.describe("company should not be able to pay @bill @installment", () => {
	test("companyBlocked", async ({ page }) => {
		test.skip(!greaterOrEqualsThan("9.6.0"), "Requires WooCommerce 9.6.0+");

		await goToProduct(page);

		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);
		await page.getByRole("textbox", { name: "Unternehmen" }).fill("Test GmbH");

		await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

		await expect(page.locator("body")).toContainText(
			"nur für Privatpersonen möglich"
		);
	});
});

test.describe("amount change should invalidate payment @installment", () => {
	test("checkoutAmountChange", async ({ page }) => {
		await goToProduct(page);

		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);

		await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await checkAmountInvalidation(page);
	});
});

test.describe("address change should invalidate payment @installment", () => {
	test("checkoutAddressChange", async ({ page }) => {
		await goToProduct(page);

		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);

		await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await checkAddressInvalidation(page);
	});
});

test.describe("address change should invalidate payment @express", () => {
	test("expressCheckoutAddressChange", async ({ page }) => {
		await goToProduct(page);

		await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			express: true,
		});

		await checkAddressInvalidation(page);
	});
});

test.describe("amount change should invalidate payment @express", () => {
	test("expressCheckoutAmountChange", async ({ page }) => {
		await goToProduct(page);

		await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			express: true,
		});

		await checkAmountInvalidation(page);
	});
});

test.describe("shipping address must equal billing address for easyCredit", () => {
	test("blocksCheckoutShippingDiffersFromBillingShowsError", async ({ page }) => {
		await goToProduct(page);

		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");

		await fillBlocksCheckout(page);

		const billingFields = page.locator("#billing-fields");

		await doWithRetry(async () => {
			const sameAsBillingCheckbox = page.getByLabel(
				"Gleiche Adresse als Rechnungsadresse verwenden"
			);
			await sameAsBillingCheckbox.setChecked(false);
			await expect(billingFields).toBeVisible();
		});

		await fillBlocksCheckout(page, billingFields);

		await billingFields
			.getByRole("textbox", { name: "Postleitzahl" })
			.fill("12345");

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("body")).toContainText(
			"Zur Zahlung mit easyCredit muss die Rechnungsadresse mit der Lieferadresse übereinstimmen."
		);
	});
});

test.describe("product below amount constraint should not be buyable @bill @installment", () => {
	test("productBelowAmountConstraints", async ({ page }) => {
		await goToProduct(page, "below50");
		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);

		for (let paymentType of [PaymentTypes.BILL, PaymentTypes.INSTALLMENT]) {
			await page
				.locator(`easycredit-checkout-label[payment-type=${paymentType}]`)
				.click();
			await expect(
				page.locator(`easycredit-checkout[payment-type=${paymentType}]`)
			).toContainText("liegt außerhalb der zulässigen Beträge");
		}
	});
});

test.describe("product above amount constraint should not be buyable @bill @installment", () => {
	test("productAboveAmountConstraints", async ({ page }) => {
		await goToProduct(page, "above10000");
		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);

		for (let paymentType of [PaymentTypes.BILL, PaymentTypes.INSTALLMENT]) {
			await page
				.locator(`easycredit-checkout-label[payment-type=${paymentType}]`)
				.click();
			await expect(
				page.locator(`easycredit-checkout[payment-type=${paymentType}]`)
			).toContainText("liegt außerhalb der zulässigen Beträge");
		}
	});
});

test.describe("last item in stock can be purchased @installment", () => {
	test.beforeEach(() => {
		setProductStock(LAST_STOCK_SKU, 1);
	});

	test("lastStockItemInstallmentCheckout", async ({ page }) => {
		await goToProduct(page, LAST_STOCK_SKU);
		await addCurrentProductToCart(page);
		await fillBlocksCheckout(page);

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("body")).not.toContainText(
			"ist ausverkauft und kann nicht gekauft werden"
		);

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await confirmOrder({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});

test.describe("order without authorization should not be possible", () => {
	test("orderWithoutAuthorizationRestricted", async ({ page }) => {
		await goToProduct(page);
		await addCurrentProductToCart(page);

		await page.goto("index.php/checkout/");
		await fillBlocksCheckout(page);

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
			selectOnly: true,
		});

		await delay(1000);

		await page.locator(".wc-block-components-checkout-place-order-button").click();
		await page.getByRole("button", { name: "Akzeptieren" }).click({ force: true });

		await expect(page.locator("body")).toContainText("Weiter");
	});
});
