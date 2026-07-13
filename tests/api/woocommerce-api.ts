import { execSync } from "child_process";
import {
	enableBlocksCheckoutTermsCheckbox,
	usesBlocksCheckout,
} from "../../scripts/enable-blocks-checkout-terms-checkbox";

export const WP_ENV_CLI_SKIP_REASON =
	"wp-env CLI is not available in this environment (e.g. Playwright in Docker without wp-env)";

let wpEnvCliAvailable: boolean | undefined;

export function isWpEnvCliAvailable(): boolean {
	if (wpEnvCliAvailable !== undefined) {
		return wpEnvCliAvailable;
	}

	try {
		execSync("npm exec wp-env run cli -- wp option get blogname", {
			encoding: "utf8",
			stdio: "pipe",
			timeout: 15_000,
		});
		wpEnvCliAvailable = true;
	} catch {
		wpEnvCliAvailable = false;
	}

	return wpEnvCliAvailable;
}

function parseWpCliOutput(output: string): string {
	const lines = output
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean);

	for (let i = lines.length - 1; i >= 0; i--) {
		const line = lines[i];
		if (
			line.startsWith("ℹ") ||
			line.startsWith("✔") ||
			line.includes("Ran `") ||
			line.startsWith("Deprecated:")
		) {
			continue;
		}

		return line;
	}

	return "";
}

function runWpCli(command: string): string {
	return parseWpCliOutput(
		execSync(
			`npm exec wp-env run cli -- sh -c '${command.replace(/'/g, "'\\''")}'`,
			{ encoding: "utf8" },
		),
	);
}

function runWpCliShell(command: string): string {
	return parseWpCliOutput(
		execSync(
			`npm exec wp-env run cli -- sh -c '${command.replace(/'/g, "'\\''")}'`,
			{ encoding: "utf8" },
		),
	);
}

export function setProductStock(sku: string, quantity: number): void {
	const productId = runWpCli(
		`wp wc product list --sku=${sku} --field=id --user=admin`,
	);

	if (!productId) {
		throw new Error(`Product with SKU "${sku}" not found`);
	}

	runWpCli(
		`wp wc product update ${productId} --manage_stock=true --stock_quantity=${quantity} --user=admin`,
	);
}

export function ensureClassicCheckoutPage(): void {
	const { ensureClassicCheckoutPage: ensureClassicCheckoutPageScript } =
		require("../../scripts/ensure-classic-checkout-page") as typeof import("../../scripts/ensure-classic-checkout-page");

	const runWpCli = (command: string) => {
		try {
			return parseWpCliOutput(
				execSync(
					`npm exec wp-env run cli -- sh -c '${command.replace(/'/g, "'\\''")}'`,
					{ encoding: "utf8" },
				),
			);
		} catch {
			return "";
		}
	};

	const result = ensureClassicCheckoutPageScript((command: string) =>
		runWpCli(command),
	);

	if (result === "classic_checkout_failed") {
		throw new Error("Failed to ensure classic checkout page for E2E tests");
	}
}

export function ensureNativeTermsPage(): void {
	const pageId = runWpCli("wp option get woocommerce_terms_page_id");

	if (!pageId || pageId === "0") {
		const termsPageId = runWpCli(
			'wp post create --post_type=page --post_title="AGB" --post_name="agb" --post_status=publish --post_content="Test AGB." --porcelain',
		);

		if (!termsPageId) {
			throw new Error("Failed to create WooCommerce terms page for E2E tests");
		}

		runWpCli(`wp option update woocommerce_terms_page_id ${termsPageId}`);
	}

	if (usesBlocksCheckout(process.env.VERSION ?? "")) {
		enableBlocksCheckoutTermsCheckbox(runWpCliShell);
	}
}
