#!/usr/bin/env node

const { execSync } = require("child_process");
const {
	CLASSIC_CHECKOUT_SLUG,
	ensureClassicCheckoutPage,
} = require("./ensure-classic-checkout-page");

const runWpCli = (command) =>
	execSync(`npm exec wp-env run cli -- sh -c '${command.replace(/'/g, "'\\''")}'`, {
		encoding: "utf8",
		stdio: ["pipe", "pipe", "inherit"],
	}).toString();

const result = ensureClassicCheckoutPage(runWpCli);

if (result === "classic_checkout_failed") {
	console.error("Failed to create classic checkout page.");
	process.exit(1);
}

console.log(
	result === "classic_checkout_created"
		? "Created classic checkout page."
		: "Classic checkout page already exists.",
);
console.log(`http://localhost:8888/index.php/${CLASSIC_CHECKOUT_SLUG}/`);
