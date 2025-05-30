import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown, delay, greaterOrEqualsThan } from "./utils";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

const goToOrderList = async (page) => {
	await page.goto("/wp-admin/admin.php?page=wc-orders&status=wc-processing");
}

const goToPluginSettings = async (page) => {
	await page.goto(
		"/wp-admin/admin.php?page=wc-settings&tab=checkout&section=easycredit"
	);
}

test("settingsCheck", async ({ page }) => {
	await goToPluginSettings(page)

	page.on("dialog", async (dialog) => {
		expect(dialog.message()).toContain("Die Zugangsdaten sind korrekt");
		await dialog.accept();
	});

    await page
		.locator("#woocommerce_easycredit_api_verify_credentials")
		.click();
});

test.describe("Check order listing page for merchant widget", () => {
	test("checkOrderListingPage", async ({ page }) => {
		if (!greaterOrEqualsThan("7.4.1")) {
			test.skip();
			return;
		}

		await goToOrderList(page);

		await delay(1000);

		await test.step("check for merchant status widget in listing view", async () => {
			expect(
				page
					.locator(
						"table easycredit-merchant-status-widget.hydrated"
					)
					.first()
			).toBeVisible();
		});
	});
});

test.describe("Check order detail page for merchant widget, manager & prevent shipping address change", () => {
	test("checkOrderDetailPage", async ({ page }) => {
		if (!greaterOrEqualsThan("7.4.1")) {
			test.skip();
			return;
		}

		await goToOrderList(page);

		// go to first order
		await page.locator("table .order-view").first().click();

		await delay(1000);

		await test.step("check for merchant status widget in detail view", async () => {
			expect(
				page.locator(
					"#order_data easycredit-merchant-status-widget.hydrated"
				)
			).toBeVisible();
		});

		await test.step("check for merchant manager in detail view", async () => {
			expect(
				page.locator("#order_data easycredit-merchant-status-widget.hydrated")
			).toBeVisible();
		});

		await test.step("check if prevent shipping address note is present", async () => {
			expect(page.locator(".order_data_column_container")).toContainText(
				"nicht nachträglich verändert werden"
			);
		});
	});
});
