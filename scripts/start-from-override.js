#!/usr/bin/env node

const fs = require("fs");
const path = require("path");
const { spawnSync } = require("child_process");
const { getMatrixIncludes } = require("./wp-env-matrix");

const ROOT = process.cwd();
const OVERRIDE_PATH = path.join(ROOT, ".wp-env.override.json");
const GENERATOR_SCRIPT = path.join(
	__dirname,
	"generate-wp-env-from-test-yml.js",
);
const SETUP_SCRIPT = path.join(__dirname, "setup-woocommerce.js");

function run(cmd, args, options = {}) {
	const result = spawnSync(cmd, args, {
		stdio: "inherit",
		...options,
	});
	if (result.error) {
		throw result.error;
	}
	if (typeof result.status === "number" && result.status !== 0) {
		process.exit(result.status);
	}
}

function ensureOverride() {
	if (fs.existsSync(OVERRIDE_PATH)) {
		return;
	}

	console.log(
		".wp-env.override.json not found, generating from latest test.yml combination...",
	);
	run("node", [GENERATOR_SCRIPT, "--latest"], { cwd: ROOT });

	if (!fs.existsSync(OVERRIDE_PATH)) {
		throw new Error(
			"Failed to create .wp-env.override.json. Please check the generator script output.",
		);
	}
}

function readWooVersion() {
	const raw = fs.readFileSync(OVERRIDE_PATH, "utf8");
	const cfg = JSON.parse(raw);

	// Preferred: explicit env var from override
	if (cfg.env && cfg.env.WOOCOMMERCE_VERSION) {
		return cfg.env.WOOCOMMERCE_VERSION;
	}

	const core = cfg.core;
	const php = cfg.phpVersion;

	if (!core || !php) {
		throw new Error(
			".wp-env.override.json must contain \"core\" and \"phpVersion\".",
		);
	}

	const match = /^WordPress\/WordPress#(.+)$/.exec(core);
	if (!match) {
		throw new Error(
			`Unexpected core format "${core}". Expected "WordPress/WordPress#<version>".`,
		);
	}

	const wp = match[1];
	const includes = getMatrixIncludes();

	const entry = includes.find(
		(e) =>
			e["php-version"] === php && e["wordpress-version"] === wp,
	);

	if (!entry) {
		throw new Error(
			`No matching matrix entry found for php-version=${php}, wordpress-version=${wp} in test.yml.`,
		);
	}

	return entry["woocommerce-version"];
}

function main() {
	try {
		ensureOverride();
		const wooVersion = readWooVersion();

		console.log(`Using WooCommerce VERSION=${wooVersion} from override file.`);

		// Start wp-env
		run("pnpm", ["exec", "wp-env", "start"], {
			cwd: ROOT,
			env: process.env,
		});

		// Run WooCommerce setup with VERSION from override
		run("node", [SETUP_SCRIPT], {
			cwd: ROOT,
			env: { ...process.env, VERSION: wooVersion },
		});
	} catch (err) {
		console.error(`Error: ${err.message}`);
		process.exit(1);
	}
}

main();

