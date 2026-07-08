import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../../helpers/utils";
import {
	goToProduct,
	addCurrentProductToCart,
	fillBlocksCheckout,
	selectAndProceed,
	goThroughPaymentPage,
	confirmOrder,
} from "../../helpers/common";
import { PaymentTypes } from "../../helpers/types";
import { setProductStock } from "../../api/woocommerce-api";

const LAST_STOCK_SKU = "lastone";
const SOLD_OUT_TEXT = "ist ausverkauft und kann nicht gekauft werden";

const acceptGermanizedAgreements = async (page) => {
	await test.step("Accept Germanized agreements", async () => {
		const checkboxes = page.locator(
			".wc-gzd-block-checkout-checkboxes input[type='checkbox']:visible, #checkbox-legal:visible, input[name='legal']:visible",
		);
		const count = await checkboxes.count();

		for (let i = 0; i < count; i++) {
			const checkbox = checkboxes.nth(i);
			if (!(await checkbox.isChecked())) {
				await checkbox.check({ force: true });
			}
		}
	});
};

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Germanized last stock regression @compat @compat-germanized", () => {
	test.beforeEach(() => {
		setProductStock(LAST_STOCK_SKU, 1);
	});

	test("blocks checkout return does not show sold out", async ({ page }) => {
		await goToProduct(page, LAST_STOCK_SKU);
		await addCurrentProductToCart(page);
		await fillBlocksCheckout(page);

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await acceptGermanizedAgreements(page);

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("body")).not.toContainText(SOLD_OUT_TEXT);

		await acceptGermanizedAgreements(page);

		await confirmOrder({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});
