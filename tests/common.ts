import { test, expect } from "@playwright/test";
import { delay, randomize, clickWithRetry } from "./utils";
import { PaymentTypes } from "./types";

export const goToCart = async (page) => {
	await test.step(`Go to cart`, async () => {
		await page.goto(`/index.php/cart/`);
	});
};

export const goToProduct = async (page, sku = "regular") => {
	await test.step(`Go to product (sku: ${sku}}`, async () => {
		await page.goto(`/index.php/produkt/${sku}/`);
	});
};

export const addCurrentProductToCart = async (page) => {
	await test.step(`Add current product to cart and go to checkout`, async () => {
		await page.getByRole('button', { name: 'In den Warenkorb' }).first().click();
		await page.goto('index.php/checkout/');
	});
};

export const fillClassicCheckout = async (page) => {
	await page
		.getByRole("textbox", { name: "Vorname *" })
		.fill(randomize("Ralf"));
	await page.getByRole("textbox", { name: "Nachname *" }).fill("Ratenkauf");
	await page
		.getByRole("textbox", { name: "Straße *" })
		.fill("Beuthener Str. 25");
	await page.getByRole("textbox", { name: "Postleitzahl *" }).fill("90471");
	await page.getByRole("textbox", { name: "Ort / Stadt *" }).fill("Nürnberg");
	await page
		.getByLabel("E-Mail-Adresse *")
		.fill("ralf.ratenkauf@teambank.de");
}

export const fillBlocksCheckout = async (page, scope?: any) => {
	const root = scope ?? page;

	if (scope === undefined) { // only for the main form, not for the billing-fields
		await page.getByLabel("E-Mail-Adresse").fill("ralf.ratenkauf@teambank.de");
	}

	await root
		.getByRole("textbox", { name: "Vorname" })
		.fill(randomize("Ralf"));
	await root.getByRole("textbox", { name: "Nachname" }).fill("Ratenkauf");
	await root
		.getByRole("textbox", { name: "Adresse", exact: true })
		.fill("Beuthener Str. 25");
	await root.getByRole("textbox", { name: "Postleitzahl" }).fill("90471");
	await root.getByRole("textbox", { name: "Stadt" }).fill("Nürnberg");
	await root
		.getByRole("textbox", { name: "Telefon (optional)" })
		.fill("012345678");
};

export const startExpress = async ({
	page,
	paymentType,
}: {
	page: any;
	paymentType: PaymentTypes;
}) => {
	await test.step(`Start express checkout (${paymentType})`, async () => {
		if (paymentType === PaymentTypes.INSTALLMENT) {
			await page
				.locator("button")
				.filter({ hasText: "in Raten" })
				.click();
			await page.getByText("Akzeptieren", { exact: true }).click();
		}
		if (paymentType === PaymentTypes.BILL) {
			await page
				.locator("button")
				.filter({ hasText: "auf Rechnung" })
				.click();
			await page.getByText("Akzeptieren", { exact: true }).click();
		}
	});
};

export const goThroughPaymentPage = async ({
	page,
	paymentType,
	express = false,
	switchPaymentType = false,
}: {
	page: any;
	paymentType: PaymentTypes;
	express?: boolean;
	switchPaymentType?: boolean;
}) => {
	await test.step(`easyCredit Payment (${paymentType})`, async () => {
		await page.getByTestId("uc-deny-all-button").click();

		if (switchPaymentType) {
			const switchButton = await page
				.locator(".paymentoptions")
				.getByText(
					paymentType === PaymentTypes.INSTALLMENT
						? "Rechnung"
						: "Ratenkauf"
				);
			await expect(switchButton).toBeVisible();
			await switchButton.click({ force: true });
		}

		await page.getByRole("button", { name: "Dateneingabe" }).click();

		if (express) {
			await page.locator("#firstName").fill(randomize("Ralf"));
			await page.locator("#lastName").fill("Ratenkauf");
		}

		await page.locator("#dateOfBirth").getByRole("textbox").fill("05.04.1972");

		if (express) {
			await page
				.locator("#email")
				.getByRole("textbox")
				.fill("ralf.ratenkauf@teambank.de");
		}

		await page
			.locator("#mobilfunknummer")
			.getByRole("textbox")
			.fill("1703404848");
		await page
			.locator("app-ratenkauf-iban-input-dumb")
			.getByRole("textbox")
			.fill("DE12500105170648489890");

		if (express) {
			await page.locator("#streetAndNumber").fill("Beuthener Str. 25");
			await page.locator("#postalCode").fill("90402");
			await page.locator("#city").fill("Nürnberg");
		}

		// Retry clicking until checkbox is checked
		const maxRetries = 3;
		for (let i = 0; i < maxRetries; i++) {
			await page.locator("#sepamandat tbk-svg-icon").click({force: true});
			await delay(500);
			const isChecked = await page.locator("#agreeSepa").isChecked();
			if (isChecked) {
				break;
			}
			if (i === maxRetries - 1) {
				throw new Error('SEPA checkbox was not checked after multiple attempts');
			}
		}
		
		await page.locator("#next-btn").click();

		await delay(500);
		await clickWithRetry(
			page.getByRole("button", { name: "Zahlung übernehmen" })
		);
	});
};

export const selectAndProceed = async ({
  page,
  paymentType,
  selectOnly = false,
}: {
  page: any;
  paymentType: PaymentTypes;
  selectOnly?: boolean;
}) => {
  await test.step(`Start standard checkout (${paymentType})`, async () => {
    await page.waitForTimeout(2000);

	if (paymentType === PaymentTypes.INSTALLMENT) {
      await page
        .locator("easycredit-checkout-label[payment-type=INSTALLMENT]")
        .click();
      if (!selectOnly) {
        await page.locator("easycredit-checkout")
          .getByRole("button", { name: "Weiter zu easyCredit-Ratenkauf" })
          .click();
      }
      return;
    }
    if (paymentType === PaymentTypes.BILL) {
      await page
        .locator("easycredit-checkout-label[payment-type=BILL]")
        .click();
	  if (!selectOnly) {
        await page.locator("easycredit-checkout")
          .getByRole("button", { name: "auf Rechnung zahlen" })
          .click();
      }
      return;
    }
  });
}

export const confirmOrder = async ({
	page,
	paymentType,
	isClassicCheckout
}: {
	page: any;
	paymentType: PaymentTypes;
	isClassicCheckout?: boolean;
}) => {
	await test.step(`Confirm order`, async () => {
		await expect(page.locator("easycredit-checkout-label")).toContainText(
			paymentType === PaymentTypes.INSTALLMENT
				? "Ratenkauf"
				: "Rechnung"
		);

		const orderSummaryBlock = isClassicCheckout ? page.locator(".woocommerce-checkout-review-order-table") : page.locator(".wp-block-woocommerce-checkout-totals-block");
		if (paymentType === PaymentTypes.INSTALLMENT) {
			await expect
				.soft(orderSummaryBlock)
				.toContainText(/Zinsen für Ratenzahlung|Interest/);
		} else {
			await expect
				.soft(orderSummaryBlock)
				.not.toContainText(/Zinsen für Ratenzahlung|Interest/);
		}

		await page.getByRole("button", { name: /Kostenpflichtig bestellen|Bestellung aufgeben/ }).click();

		/* Success Page */
		await expect(page).toHaveURL(/order-received/);
	});
};

export const checkAmountInvalidation = async (page) => {
	await test.step(`Check amount change invalidates payment`, async () => {
		// Wait for checkout to be loaded
		await page.locator("easycredit-checkout").waitFor({ state: 'visible' });

		// Go to cart and change quantity to invalidate the payment
		await page.goto('index.php/cart/');
		const quantityInput = page.locator('.wc-block-components-quantity-selector input').first();
		await quantityInput.fill('2');
		
		// Wait for the batch API request to complete after changing quantity
		await page.waitForResponse(response => 
			response.url().includes('wp-json/wc/store/v1/batch')
		);
		
		// Go back to checkout and wait for it to be loaded
		await page.goto('index.php/checkout/');
		// Verify payment is invalidated - payment-plan attribute should not be set
		await expect(page.locator("easycredit-checkout")).not.toHaveAttribute('payment-plan');
	});
};

export const checkAddressInvalidation = async (page) => {
	await test.step(`Check address change invalidates payment`, async () => {
		// Wait for checkout to be loaded
		await page.locator("easycredit-checkout").waitFor({ state: 'visible' });

		// Change address to invalidate the payment
		await page
			.locator('[aria-label="Lieferadresse bearbeiten"], [aria-label="Rechnungsadresse bearbeiten"], [aria-label="Adresse bearbeiten"]')
		 .first()
		 .click();

		const addressField = page.getByRole("textbox", { name: "Postleitzahl", exact: true });
		await addressField.fill("90403");
		await addressField.blur();

		await page.waitForResponse(response => 
			response.url().includes('wp-json/wc/store/v1/batch')
		);

		await expect(page.locator("easycredit-checkout")).not.toHaveAttribute('payment-plan');
	});
};
