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
	await page.getByRole("textbox", { name: "Telefon *" }).fill("012345678");
	await page
		.getByLabel("E-Mail-Adresse *")
		.fill("ralf.ratenkauf@teambank.de");
}

export const fillBlocksCheckout = async (page) => {
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

export const goThroughPaymentPage = async ({
  page,
  paymentType,
  express = false,
}: {
  page: any;
  paymentType: PaymentTypes;
  express?: boolean;
}) => {
  await test.step(`easyCredit-Ratenkauf Payment`, async () => {
    await page.getByTestId("uc-deny-all-button").click();

    await expect(
      page.getByRole("heading", {
        name:
          paymentType === PaymentTypes.INSTALLMENT
            ? "Monatliche Wunschrate"
            : "Ihre Bezahloptionen",
      })
    ).toBeVisible();

    await page.getByRole("button", { name: "Weiter zur Dateneingabe" }).click();

    if (express) {
      await page.locator("#firstName").fill(randomize("Ralf"));
      await page.locator("#lastName").fill("Ratenkauf");
    }

    await page.locator("#dateOfBirth").fill("05.04.1972");

    if (express) {
      await page.locator("#email").getByRole('textbox').fill("ralf.ratenkauf@teambank.de");
    }

    await page.locator("tbk-vorwahldropdown .tel-wrapper").click();
    await page.locator('tbk-vorwahldropdown').locator('li').filter({ hasText: '+49' }).getByRole('paragraph').click();
    await page.locator('#mobilfunknummer').getByRole('textbox').fill('1703404848');
    await page.locator('app-ratenkauf-iban-input-dumb').getByRole('textbox').fill("DE12500105170648489890");

    if (express) {
      await page.locator("#streetAndNumber").fill("Beuthener Str. 25");
      await page.locator("#postalCode").fill("90402");
      await page.locator("#city").fill("Nürnberg");
    }

    await page.locator("#agreeAll").click();

    await delay(500);
    await clickWithRetry(
      page.getByRole("button", { name: "Zahlungswunsch prüfen" })
    );

    await delay(500);
    await page.getByRole("button", { name: "Zahlungswunsch übernehmen" }).click();
  });
};

export const selectAndProceed = async ({
  page,
  paymentType,
}: {
  page: any;
  paymentType: PaymentTypes;
}) => {
  await test.step(`Start standard checkout (${paymentType})`, async () => {
    if (paymentType === PaymentTypes.INSTALLMENT) {
      await page
        .locator("easycredit-checkout-label[payment-type=INSTALLMENT]")
        .click();
      await page.getByRole("button", { name: "Weiter zu easyCredit-Ratenkauf" }).click();
      return;
    }
    if (paymentType === PaymentTypes.BILL) {
      await page
        .locator("easycredit-checkout-label[payment-type=BILL]")
        .click();
      await page
        .getByRole("button", { name: "Weiter zu easyCredit-Rechnung" })
        .click();
      return;
    }
  });
}

export const confirmOrder = async ({
	page,
	paymentType
}: {
	page: any;
	paymentType: PaymentTypes;
}) => {
	await test.step(`Confirm order`, async () => {
		await expect(page.locator("easycredit-checkout-label")).toContainText(
			paymentType === PaymentTypes.INSTALLMENT
				? "Ratenkauf"
				: "Rechnung"
		);

		if (paymentType === PaymentTypes.INSTALLMENT) {
			await expect
				.soft(page.locator(".woocommerce-table--order-details tfoot"))
				.toContainText("Zinsen für Ratenzahlung");
		} else {
			await expect
				.soft(page.locator(".woocommerce-table--order-details tfoot"))
				.not.toContainText("Zinsen für Ratenzahlung");
		}

		await page
			.getByRole("button", { name: "pflichtig bestellen" })
			.click();

		/* Success Page */
		await expect(page).toHaveURL(/order-received/);
	});
};
