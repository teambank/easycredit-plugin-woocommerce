import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown, greaterOrEqualsThan } from "../helpers/utils";
import {
	goToProduct,
	fillClassicCheckout,
	fillBlocksCheckout,
	addCurrentProductToCart,
	selectAndProceed,
	goThroughPaymentPage,
	acceptNativeTermsCheckbox,
	expectNativeTermsCheckbox,
	expectNativeTermsValidationError,
	acceptEasycreditPrivacyModal,
} from "../helpers/common";
import { PaymentTypes } from "../helpers/types";
import { ensureNativeTermsPage, isWpEnvCliAvailable, WP_ENV_CLI_SKIP_REASON } from "../api/woocommerce-api";

const isBlocksCheckout = greaterOrEqualsThan("8.3");

test.beforeEach(scaleDown);
test.beforeEach(() => {
	test.skip(!isWpEnvCliAvailable(), WP_ENV_CLI_SKIP_REASON);
	ensureNativeTermsPage();
});
test.afterEach(takeScreenshot);

const prepareCheckout = async (page, isClassic: boolean) => {
	await goToProduct(page);

	if (isClassic) {
		await page.getByRole("button", { name: "In den Warenkorb" }).click();
		await page.goto("index.php/checkout/");
		await fillClassicCheckout(page);
		return;
	}

	await addCurrentProductToCart(page);
	await fillBlocksCheckout(page);
};

test.describe("native WC terms defer classic @installment", () => {
	test.skip(isBlocksCheckout, "Classic checkout matrix only");

	test("redirect proceeds without accepting terms", async ({ page }) => {
		await prepareCheckout(page, true);
		await expectNativeTermsCheckbox(page, { isClassicCheckout: true, checked: false });

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page).toHaveURL(/ratenkauf\.easycredit\.de/i, { timeout: 25_000 });
	});

	test("terms are required after financing approval", async ({ page }) => {
		await prepareCheckout(page, true);
		await expectNativeTermsCheckbox(page, { isClassicCheckout: true, checked: false });

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("easycredit-checkout")).toHaveAttribute("payment-plan");
		await expectNativeTermsCheckbox(page, { isClassicCheckout: true, checked: false });

		const placeOrderButton = page.getByRole("button", {
			name: /jetzt kaufen|pflichtig bestellen|Bestellung aufgeben/i,
		});
		await placeOrderButton.click();

		await expect(page).not.toHaveURL(/order-received/);
		await expectNativeTermsValidationError(page, { isClassicCheckout: true });

		await acceptNativeTermsCheckbox(page, { isClassicCheckout: true });
		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
});

test.describe("native WC terms defer blocks @installment", () => {
	test.skip(!isBlocksCheckout, "Blocks checkout matrix only");

	test("redirect proceeds without accepting terms", async ({ page }) => {
		await prepareCheckout(page, false);
		await expectNativeTermsCheckbox(page, { isClassicCheckout: false, checked: false });

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page).toHaveURL(/ratenkauf\.easycredit\.de/i, { timeout: 25_000 });
	});

	test("terms are required after financing approval", async ({ page }) => {
		await prepareCheckout(page, false);
		await expectNativeTermsCheckbox(page, { isClassicCheckout: false, checked: false });

		await selectAndProceed({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await goThroughPaymentPage({
			page,
			paymentType: PaymentTypes.INSTALLMENT,
		});

		await expect(page.locator("easycredit-checkout")).toHaveAttribute("payment-plan");
		await expectNativeTermsCheckbox(page, { isClassicCheckout: false, checked: false });

		const placeOrderButton = page.locator(
			".wc-block-components-checkout-place-order-button",
		);
		await placeOrderButton.click();
		await acceptEasycreditPrivacyModal(page);

		await expect(page).not.toHaveURL(/order-received/);
		await expectNativeTermsValidationError(page, { isClassicCheckout: false });

		await acceptNativeTermsCheckbox(page, { isClassicCheckout: false });
		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
});
