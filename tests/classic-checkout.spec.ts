import { test, expect } from "@playwright/test";
import { randomize, takeScreenshot, scaleDown } from "./utils";
import { goToProduct, fillClassicCheckout, goThroughPaymentPage, confirmOrder, selectAndProceed } from "./common";
import { PaymentTypes } from "./types";

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
		});
	});
});

test.describe("Go through @express @installment", () => {
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
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});

test.describe("go through @express @bill", () => {
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
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.BILL,
		});
	});
});

test.describe("Go through @express @installment with variable product ", () => {
	test("expressCheckoutWithVariableProductInstallment", async ({ page }) => {
		await goToProduct(page, "variable");

		await page.getByLabel("Size").selectOption("");
		await expect(
			page.locator("easycredit-express-button")
		).not.toBeVisible();

		await page.getByLabel("Size").selectOption("medium");
		await expect(
			page.locator("easycredit-express-button")
		).not.toBeVisible();

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
			express: true,
		});
		await confirmOrder({
			page: page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});
/*
test.describe("company should not be able to pay @bill @installment", () => {
  test("companyBlocked", async ({ page }) => {

  })
})

test.describe("amount change should invalidate payment @installment", () => {
  test("checkoutAmountChange", async ({ page }) => {

  })
})

test.describe("address change should invalidate payment @installment", () => {
  test("checkoutAddressChange", async ({ page }) => {

  })
})

test.describe("address change should invalidate payment @express", () => {
  test("expressCheckoutAddressChange", async ({ page }) => {

  })
})

test.describe("amount change should invalidate payment @express", () => {
  test("expressCheckoutAmountChange", async ({ page }) => {

  })
})

test.describe("product below amount constraint should not be buyable @bill @installment", () => {
  test("productBelowAmountConstraints", async ({ page }) => {

  })
})

test.describe("product above amount constraint should not be buyable @bill @installment", () => {
  test("productAboveAmountConstraints", async ({ page }) => {

  })
})
*/