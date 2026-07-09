import { test, expect } from "@playwright/test";
import { delay, randomize, doWithRetry } from "./utils";
import {
	goThroughPaymentPageViaApi,
	resolvePaymentPageMode,
	PAYMENT_SANDBOX,
	NATIVE_TERMS_VALIDATION_PATTERN,
	blocksNativeTermsFailureLocator,
} from "../api/payment-api";
import { PaymentTypes } from "./types";

export const easycreditCheckoutLocator = (
	page,
	paymentType: PaymentTypes = PaymentTypes.INSTALLMENT,
) => page.locator(`easycredit-checkout[payment-type="${paymentType}"]`);

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
		await page.getByRole("button", { name: "In den Warenkorb" }).first().click();
		await page.goto("index.php/checkout/");
	});
};

export const fillClassicCheckout = async (page) => {
	await page
		.getByRole("textbox", { name: /^Vorname/ })
		.fill(randomize("Ralf"));
	await page.getByRole("textbox", { name: /^Nachname/ }).fill("Ratenkauf");
	await page
		.getByRole("textbox", { name: /^(Straße|Adresse)/ })
		.fill("Beuthener Str. 25");
	await page.getByRole("textbox", { name: /^Postleitzahl/ }).fill("90471");
	await page
		.getByRole("textbox", { name: /^(Ort \/ Stadt|Stadt)/ })
		.fill("Nürnberg");
	await page
		.getByLabel(/^E-Mail-Adresse/)
		.fill("ralf.ratenkauf@teambank.de");
};

export const fillClassicShippingAddress = async (
	page,
	{
		postcode = "12345",
		city = "Berlin",
		street = "Andere Str. 1",
	}: {
		postcode?: string;
		city?: string;
		street?: string;
	} = {},
) => {
	const shippingFields = page.locator(".woocommerce-shipping-fields");

	await page.getByLabel(/Lieferung an eine andere Adresse/i).check();
	await shippingFields.getByRole("textbox", { name: /^Vorname/ }).fill("Ralf");
	await shippingFields.getByRole("textbox", { name: /^Nachname/ }).fill("Ratenkauf");
	await shippingFields
		.getByRole("textbox", { name: /^(Straße|Adresse)/ })
		.fill(street);
	await shippingFields.getByRole("textbox", { name: /^Postleitzahl/ }).fill(postcode);
	await shippingFields
		.getByRole("textbox", { name: /^(Ort \/ Stadt|Stadt)/ })
		.fill(city);
	await shippingFields.getByRole("textbox", { name: /^Postleitzahl/ }).blur();
};

export const checkClassicCompanyBlocked = async (page) => {
	await test.step(`Check company blocks easyCredit on classic checkout`, async () => {
		const widget = easycreditCheckoutLocator(page);
		await widget.waitFor({ state: "visible" });

		const companyField = page.getByRole("textbox", { name: /Firmenname/i }).first();
		await companyField.fill("Test GmbH");
		await companyField.blur();

		await expect(widget).toHaveAttribute(
			"alert",
			/nur für Privatpersonen möglich/i,
			{ timeout: 5_000 },
		);
	});
};

export const checkClassicCompanyInvalidation = async (page) => {
	await test.step(`Check company change invalidates payment on classic checkout`, async () => {
		const widget = easycreditCheckoutLocator(page);
		await widget.waitFor({ state: "visible" });

		const companyField = page.getByRole("textbox", { name: /Firmenname/i }).first();
		await companyField.fill("Test GmbH");
		await companyField.blur();

		await expect(widget).toHaveAttribute(
			"alert",
			/nur für Privatpersonen möglich/i,
			{ timeout: 5_000 },
		);

		await expect(widget).not.toHaveAttribute("payment-plan");
	});
};

export const checkClassicShippingDiffersFromBilling = async (page) => {
	await test.step(`Check differing shipping address shows validation error`, async () => {
		const widget = easycreditCheckoutLocator(page);
		await widget.waitFor({ state: "visible" });
		await fillClassicShippingAddress(page);

		await expect(widget).toHaveAttribute(
			"alert",
			/Rechnungsadresse mit der Lieferadresse übereinstimmen/i,
			{ timeout: 5_000 },
		);
	});
};

export const fillBlocksCheckout = async (page, scope?: any) => {
	const root = scope ?? page;

	if (scope === undefined) {
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
	viaApi,
}: {
	page: any;
	paymentType: PaymentTypes;
	express?: boolean;
	switchPaymentType?: boolean;
	/** Use payment-page API instead of hosted UI. Defaults to EASYCREDIT_PAYMENT_API env. */
	viaApi?: boolean;
}) => {
	const mode = resolvePaymentPageMode(viaApi);

	if (mode === "api") {
		return test.step(`easyCredit Payment via API (${paymentType})`, async () => {
			await goThroughPaymentPageViaApi({ page, paymentType, express });
		});
	}

	return test.step(`easyCredit Payment (${paymentType})`, async () => {
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

		await page.getByRole("button", { name: "Weiter" }).click();

		await page
			.locator("#mobilfunknummer")
			.getByRole("textbox")
			.fill(PAYMENT_SANDBOX.phone);

		await doWithRetry(async () => {
			await page.getByRole("button", { name: "SMS-TAN senden" }).click();
			await delay(500);
			const mtanInput = page.locator("#mTAN").getByRole("textbox");
			const canFillMtan =
				(await mtanInput.isVisible()) && (await mtanInput.isEditable());
			if (!canFillMtan) {
				throw new Error("mTAN input is not fillable yet");
			}
		});

		await page
			.locator("#mTAN")
			.getByRole("textbox")
			.fill(PAYMENT_SANDBOX.tan);

		await doWithRetry(async () => {
			await page.getByRole("button", { name: "Zur Dateneingabe" }).click();
		});

		if (express) {
			await page.locator("#firstName").fill(randomize("Ralf"));
			await page.locator("#lastName").fill("Ratenkauf");
		}

		await page.locator("#dateOfBirth").getByRole("textbox").fill("05.04.1972");

		if (express) {
			await page
				.locator("#email")
				.getByRole("textbox")
				.fill(PAYMENT_SANDBOX.expressEmail);
		}

		await page
			.locator("app-ratenkauf-iban-input-dumb")
			.getByRole("textbox")
			.fill(PAYMENT_SANDBOX.iban);

		if (express) {
			await page.locator("#streetAndNumber").fill(PAYMENT_SANDBOX.street);
			await page.locator("#postalCode").fill(PAYMENT_SANDBOX.postalCode);
			await page.locator("#city").fill(PAYMENT_SANDBOX.city);
		}

		await doWithRetry(async () => {
			await page.locator("#sepamandat tbk-svg-icon").click({ force: true });
			await delay(500);
			const isChecked = await page.locator("#agreeSepa").isChecked();
			if (!isChecked) {
				throw new Error("SEPA checkbox was not checked");
			}
		});

		await page.locator("#next-btn").click();

		await delay(500);
		await doWithRetry(async () => {
			await page.getByRole("button", { name: "Zahlung übernehmen" }).click();
		});
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
				await page
					.locator("easycredit-checkout")
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
				await page
					.locator("easycredit-checkout")
					.getByRole("button", { name: "auf Rechnung zahlen" })
					.click();
			}
			return;
		}
	});
};

export const acceptBlocksLegalCheckboxes = async (page) => {
	await test.step("Accept legal checkboxes", async () => {
		const checkboxes = page.locator(
			".wc-gzd-block-checkout-checkboxes input[type='checkbox']:not(:checked), #checkbox-legal:not(:checked)",
		);
		const count = await checkboxes.count();

		for (let i = 0; i < count; i++) {
			await checkboxes.nth(i).check({ force: true });
		}

		if (count > 0) {
			await delay(500);
		}
	});
};

export const acceptGermanizedLegalCheckboxes = async (page) => {
	await test.step("Accept Germanized legal checkboxes", async () => {
		const checkboxes = page.locator(
			".wc-gzd-block-checkout-checkboxes input[type='checkbox']:visible, #checkbox-legal:visible, input[name='legal']:visible, .wc-gzd-checkbox input[type='checkbox']:visible",
		);
		const count = await checkboxes.count();

		for (let i = 0; i < count; i++) {
			const checkbox = checkboxes.nth(i);
			if (!(await checkbox.isChecked())) {
				await checkbox.check({ force: true });
			}
		}

		if (count > 0) {
			await delay(500);
		}
	});
};

export const acceptNativeTermsCheckbox = async (
	page,
	{ isClassicCheckout = false }: { isClassicCheckout?: boolean } = {},
) => {
	await test.step("Accept native WooCommerce terms", async () => {
		const terms = isClassicCheckout
			? page.locator('input[name="terms"]:visible')
			: page.locator(
					'#terms-and-conditions:visible, .wp-block-woocommerce-checkout-terms-block input[type="checkbox"]:visible',
				);

		if ((await terms.count()) === 0 || (await terms.isChecked())) {
			return;
		}

		const label = isClassicCheckout
			? page.locator('label[for="terms"]:visible, label:has(input[name="terms"]:visible)')
			: page.locator(
					'label[for="terms-and-conditions"]:visible, .wp-block-woocommerce-checkout-terms-block label:has(input[type="checkbox"])',
				);

		if (await label.count()) {
			await label.first().click();
		}

		if (!(await terms.isChecked())) {
			await terms.evaluate((el) => {
				const input = el as HTMLInputElement;
				input.checked = true;
				input.dispatchEvent(new Event("change", { bubbles: true }));
				input.dispatchEvent(new Event("input", { bubbles: true }));
			});
		}

		await expect(terms).toBeChecked();
	});
};

export const expectNativeTermsCheckbox = async (
	page,
	{
		isClassicCheckout = false,
		checked = false,
	}: { isClassicCheckout?: boolean; checked?: boolean } = {},
) => {
	await test.step("Verify native WooCommerce terms checkbox", async () => {
		const terms = isClassicCheckout
			? page.locator('input[name="terms"]:visible')
			: page.locator(
					'#terms-and-conditions:visible, .wp-block-woocommerce-checkout-terms-block input[type="checkbox"]:visible',
				);

		await expect(terms).toBeVisible();
		if (checked) {
			await expect(terms).toBeChecked();
		} else {
			await expect(terms).not.toBeChecked();
		}
	});
};

export const expectNativeTermsValidationError = async (
	page,
	{ isClassicCheckout = false }: { isClassicCheckout?: boolean } = {},
) => {
	await test.step("Verify native WooCommerce terms validation error", async () => {
		if (isClassicCheckout) {
			await expect(page.locator(".woocommerce-error")).toContainText(
				NATIVE_TERMS_VALIDATION_PATTERN,
			);
			return;
		}

		const termsFailure = blocksNativeTermsFailureLocator(page);
		await expect(termsFailure).toBeVisible();

		const legacyValidationError = page.locator(
			".wp-block-woocommerce-checkout-terms-block .wc-block-components-validation-error",
		);
		if ((await legacyValidationError.count()) > 0) {
			await expect(legacyValidationError).toContainText(
				NATIVE_TERMS_VALIDATION_PATTERN,
			);
		}
	});
};

export const acceptEasycreditPrivacyModal = async (page) => {
	await test.step("Accept easyCredit privacy modal", async () => {
		const akzeptieren = page.getByRole("button", { name: "Akzeptieren" });
		try {
			await akzeptieren.waitFor({ state: "visible", timeout: 3_000 });
			await akzeptieren.click({ force: true });
		} catch {
			// Privacy already approved or not required (e.g. bill payment).
		}
	});
};

export const confirmOrder = async ({
	page,
	paymentType,
	isClassicCheckout,
}: {
	page: any;
	paymentType: PaymentTypes;
	isClassicCheckout?: boolean;
}) => {
	await test.step(`Confirm order`, async () => {
		await expect(page.locator("easycredit-checkout-label")).toContainText(
			paymentType === PaymentTypes.INSTALLMENT ? "Ratenkauf" : "Rechnung"
		);

		const orderSummaryBlock = isClassicCheckout
			? page.locator(".woocommerce-checkout-review-order-table")
			: page.locator(".wp-block-woocommerce-checkout-totals-block");
		if (paymentType === PaymentTypes.INSTALLMENT) {
			await expect
				.soft(orderSummaryBlock)
				.toContainText(/Zinsen für Ratenzahlung|Interest/);
		} else {
			await expect
				.soft(orderSummaryBlock)
				.not.toContainText(/Zinsen für Ratenzahlung|Interest/);
		}

		const placeOrderButton = page.getByRole("button", {
			name: /jetzt kaufen|pflichtig bestellen|Bestellung aufgeben/i,
		});

		await acceptNativeTermsCheckbox(page, { isClassicCheckout });
		await acceptGermanizedLegalCheckboxes(page);
		await acceptBlocksLegalCheckboxes(page);

		await placeOrderButton.click();

		await expect(page).toHaveURL(/order-received/);
	});
};

export const checkAmountInvalidation = async (page) => {
	await test.step(`Check amount change invalidates payment`, async () => {
		await page.locator("easycredit-checkout").waitFor({ state: "visible" });

		await page.goto("index.php/cart/");
		const quantityInput = page
			.locator(".wc-block-components-quantity-selector input")
			.first();
		await quantityInput.fill("2");

		await page.waitForResponse(
			(response) =>
				response.url().includes("wp-json/wc/store/v1/batch") ||
				response.url().includes("wp-json/wc/store/v1/cart/update-item")
		);

		await page.goto("index.php/checkout/");
		await expect(page.locator("easycredit-checkout")).not.toHaveAttribute(
			"payment-plan"
		);
	});
};

export const openBlocksCheckoutAddressForEditing = async (page) => {
	const editButtons = [
		'[aria-label="Lieferadresse bearbeiten"]',
		'[aria-label="Rechnungsadresse bearbeiten"]',
		'[aria-label="Adresse bearbeiten"]',
	];

	for (const selector of editButtons) {
		const button = page.locator(selector).first();
		if ((await button.count()) > 0 && (await button.isVisible())) {
			await button.click();
			return;
		}
	}
};

export const checkAddressInvalidation = async (page) => {
	await test.step(`Check address change invalidates payment`, async () => {
		await page.locator("easycredit-checkout").waitFor({ state: "visible" });

		await openBlocksCheckoutAddressForEditing(page);

		const addressField = page.getByRole("textbox", {
			name: "Postleitzahl",
			exact: true,
		});
		await addressField.fill("90403");
		await addressField.blur();

		await expect(page.locator("easycredit-checkout")).not.toHaveAttribute(
			"payment-plan"
		);
	});
};

export const checkCompanyInvalidation = async (page) => {
	await test.step(`Check company change invalidates payment`, async () => {
		await page.locator("easycredit-checkout").waitFor({ state: "visible" });

		await openBlocksCheckoutAddressForEditing(page);

		const companyField = page.getByRole("textbox", { name: /Unternehmen|Firma/i });
		await companyField.fill("Test GmbH");
		await companyField.blur();

		await expect(page.locator("easycredit-checkout")).toHaveAttribute(
			"alert",
			/nur für Privatpersonen möglich/i
		);

		await expect(page.locator("easycredit-checkout")).not.toHaveAttribute(
			"payment-plan"
		);
	});
};
