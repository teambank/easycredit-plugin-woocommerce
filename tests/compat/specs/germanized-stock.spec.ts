import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../../helpers/utils";
import {
	goToProduct,
	addCurrentProductToCart,
	fillBlocksCheckout,
	selectAndProceed,
	goThroughPaymentPage,
	confirmOrder,
	acceptGermanizedLegalCheckboxes,
} from "../../helpers/common";
import { PaymentTypes } from "../../helpers/types";
import { setProductStock, isWpEnvCliAvailable, WP_ENV_CLI_SKIP_REASON } from "../../api/woocommerce-api";

const LAST_STOCK_SKU = "lastone";
const SOLD_OUT_TEXT = "ist ausverkauft und kann nicht gekauft werden";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Germanized last stock regression @compat @compat-germanized", () => {
	test.beforeEach(() => {
		test.skip(!isWpEnvCliAvailable(), WP_ENV_CLI_SKIP_REASON);
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

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("body")).not.toContainText(SOLD_OUT_TEXT);

		await acceptGermanizedLegalCheckboxes(page);

		await confirmOrder({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});
	});
});
