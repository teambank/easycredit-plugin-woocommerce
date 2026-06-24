import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown } from "../helpers/utils";
import { goToProduct } from "../helpers/common";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Widget should be visible @product", () => {
	test("widgetProduct", async ({ page }) => {
		await goToProduct(page);
		await expect(
			page.locator(".product .price").getByText(/Finanzieren ab.+?Monat/)
		).toBeVisible();
	});
});

test.describe("Widget should be visible outside amount constraint @product", () => {
	test("widgetProductOutsideAmount", async ({ page }) => {
		await goToProduct(page, "below50");
		await expect(
			page.locator(".product .price").getByText(/Finanzieren ab.+?Bestellwert/)
		).toBeVisible();
	});
});
