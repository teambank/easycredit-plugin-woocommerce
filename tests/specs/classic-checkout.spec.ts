import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../helpers/utils";
import {
	goToProduct,
	fillClassicCheckout,
	goThroughPaymentPage,
	confirmOrder,
	selectAndProceed,
	startExpress,
	checkClassicCompanyBlocked,
	checkClassicCompanyInvalidation,
	checkClassicShippingDiffersFromBilling,
} from "../helpers/common";
import { PaymentTypes } from "../helpers/types";
import { setProductStock, isWpEnvCliAvailable, WP_ENV_CLI_SKIP_REASON } from "../api/woocommerce-api";

const LAST_STOCK_SKU = "lastone";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Go through classic @installment", () => {
	test("standardCheckoutInstallments", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);

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
			isClassicCheckout: true,
		});
	});
});

test.describe("Go through classic @bill", () => {
	test("standardCheckoutBill", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);

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
			isClassicCheckout: true,
		});
	});
});

test.describe("Go through @express @installment", () => {
	test("expressCheckoutInstallments", async ({ page }) => {
		await goToProduct(page);

		await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			isClassicCheckout: true,
		});
	});
});

test.describe("go through @express @bill", () => {
	test("expressCheckoutBill", async ({ page }) => {
		await goToProduct(page);

		await startExpress({ page, paymentType: PaymentTypes.BILL });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.BILL,
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.BILL,
			isClassicCheckout: true,
		});
	});
});

test.describe("last item in stock can be purchased @installment", () => {
	test.beforeEach(() => {
		test.skip(!isWpEnvCliAvailable(), WP_ENV_CLI_SKIP_REASON);
		setProductStock(LAST_STOCK_SKU, 1);
	});

	test("lastStockItemInstallmentCheckout", async ({ page }) => {
		await goToProduct(page, LAST_STOCK_SKU);
		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);

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
			isClassicCheckout: true,
		});
	});
});

test.describe("Go through @express @installment with variable product ", () => {
	test("expressCheckoutWithVariableProductInstallment", async ({ page }) => {
		await goToProduct(page, "variable");

		await page.getByLabel("Size").selectOption("");
		await expect(page.locator("easycredit-express-button")).not.toBeVisible();

		await page.getByLabel("Size").selectOption("medium");
		await expect(page.locator("easycredit-express-button")).not.toBeVisible();

		await page.getByLabel("Size").selectOption("small");
		await expect(page.locator("easycredit-express-button")).toBeVisible();

		await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
			isClassicCheckout: true,
		});
	});
});

test.describe("company should not be able to pay @bill @installment", () => {
	test("companyBlocked", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);
		await checkClassicCompanyBlocked(page);
	});
});

test.describe("company change should invalidate payment @installment", () => {
	test("checkoutCompanyChange", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);

		await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

		await goThroughPaymentPage({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await checkClassicCompanyInvalidation(page);
	});
});

test.describe("shipping address must equal billing address for easyCredit", () => {
	test("classicCheckoutShippingDiffersFromBillingShowsError", async ({ page }) => {
		await goToProduct(page);

		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");

		await fillClassicCheckout(page);
		await checkClassicShippingDiffersFromBilling(page);
	});
});
