import { execSync } from "child_process";

export function setProductStock(sku: string, quantity: number): void {
	const productId = execSync(
		`npm exec wp-env run cli -- wp wc product list --sku=${sku} --field=id --user=admin`,
		{ encoding: "utf8" }
	).trim();

	if (!productId) {
		throw new Error(`Product with SKU "${sku}" not found`);
	}

	execSync(
		`npm exec wp-env run cli -- wp wc product update ${productId} --manage_stock=true --stock_quantity=${quantity} --user=admin`,
		{ encoding: "utf8" }
	);
}
