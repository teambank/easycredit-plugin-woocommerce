import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown, goToProduct } from "./utils";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test('testProductBelow200', async ({ page }) => {
	goToProduct(page, "above-10000")
})

test("testProductRegular", async ({ page }) => {
	goToProduct(page)
});

test("testProductAbove10000", async ({ page }) => {
	goToProduct(page, 'below-200')
});