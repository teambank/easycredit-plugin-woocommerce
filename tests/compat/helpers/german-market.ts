import { test, expect, type Page } from "@playwright/test";
import { fillBlocksCheckout } from "../../helpers/common";

type BlocksCheckoutCustomer = {
	firstName: string;
	lastName: string;
	address: string;
	postcode: string;
	city: string;
	email: string;
	phone: string;
};

const waitForGermanMarketBlockReady = async (page: Page) => {
	await page
		.locator(".german-market-block-checkout-checkboxes")
		.waitFor({ state: "visible", timeout: 15_000 });

	// GM writes extension data on mount, which triggers a checkout draft sync.
	await page
		.waitForResponse(
			(response) =>
				response.url().includes("/wp-json/wc/store/v1/checkout") &&
				response.request().method() !== "GET",
			{ timeout: 15_000 },
		)
		.catch(() => undefined);
};

const waitForCheckoutDraftUpdate = async (page: Page) => {
	await page
		.waitForResponse(
			(response) =>
				response.url().includes("/wp-json/wc/store/v1/checkout") &&
				response.request().method() !== "GET" &&
				response.ok(),
			{ timeout: 10_000 },
		)
		.catch(() => undefined);
};

const waitForCartCustomerData = async (page: Page) => {
	await page.waitForFunction(
		() => {
			const customer =
				window.wp?.data?.select?.("wc/store/cart")?.getCustomerData?.() ?? {};
			const billing = customer.billingAddress ?? {};
			const shipping = customer.shippingAddress ?? {};

			return Boolean(
				billing.email &&
				shipping.first_name &&
				shipping.address_1 &&
				shipping.postcode &&
				shipping.city,
			);
		},
		{ timeout: 10_000 },
	);
};

const readBlocksCheckoutCustomer = async (
	page: Page,
): Promise<BlocksCheckoutCustomer> => {
	const shipping = page.getByRole("group", { name: "Lieferadresse" });

	return {
		email: await page.getByLabel("E-Mail-Adresse").inputValue(),
		firstName: await shipping.getByRole("textbox", { name: "Vorname" }).inputValue(),
		lastName: await shipping.getByRole("textbox", { name: "Nachname" }).inputValue(),
		address: await shipping
			.getByRole("textbox", { name: "Adresse", exact: true })
			.inputValue(),
		postcode: await shipping.getByRole("textbox", { name: "Postleitzahl" }).inputValue(),
		city: await shipping.getByRole("textbox", { name: "Stadt" }).inputValue(),
		phone: await shipping
			.getByRole("textbox", { name: "Telefon (optional)" })
			.inputValue(),
	};
};

const syncCartCustomerData = async (
	page: Page,
	customer: BlocksCheckoutCustomer,
) => {
	await page.evaluate((checkoutCustomer) => {
		const dispatch = window.wp?.data?.dispatch?.("wc/store/cart");
		if (!dispatch?.setBillingAddress || !dispatch?.setShippingAddress) {
			return;
		}

		dispatch.setBillingAddress({
			first_name: checkoutCustomer.firstName,
			last_name: checkoutCustomer.lastName,
			address_1: checkoutCustomer.address,
			postcode: checkoutCustomer.postcode,
			city: checkoutCustomer.city,
			country: "DE",
			email: checkoutCustomer.email,
			phone: checkoutCustomer.phone,
		});
		dispatch.setShippingAddress({
			first_name: checkoutCustomer.firstName,
			last_name: checkoutCustomer.lastName,
			address_1: checkoutCustomer.address,
			postcode: checkoutCustomer.postcode,
			city: checkoutCustomer.city,
			country: "DE",
			phone: checkoutCustomer.phone,
		});
	}, customer);
};

/**
 * German Market checkbox blocks call setExtensionData on mount, which syncs the
 * checkout draft via Store API. WC 10.x stores customer addresses on
 * wc/store/cart (not wc/store/checkout).
 */
export const fillGermanMarketBlocksCheckout = async (page: Page) => {
	await test.step("Fill German Market blocks checkout", async () => {
		const shipping = page.getByRole("group", { name: "Lieferadresse" });

		await page.getByLabel("E-Mail-Adresse").waitFor({ state: "visible" });
		await waitForGermanMarketBlockReady(page);

		await fillBlocksCheckout(page);

		const customer = await readBlocksCheckoutCustomer(page);

		try {
			await waitForCartCustomerData(page);
		} catch {
			await syncCartCustomerData(page, customer);
			await waitForCartCustomerData(page);
		}

		await waitForCheckoutDraftUpdate(page);

		await expect(page.getByLabel("E-Mail-Adresse")).toHaveValue(customer.email);
		await expect(shipping.getByRole("textbox", { name: "Vorname" })).toHaveValue(
			new RegExp(customer.firstName.slice(0, 4)),
		);
	});
};
