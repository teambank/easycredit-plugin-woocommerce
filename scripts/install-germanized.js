#!/usr/bin/env node

const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const ROOT = path.join(__dirname, "..");
const TARGET_DIR = path.join(ROOT, "src/plugins/woocommerce-germanized");
const MAIN_FILE = path.join(TARGET_DIR, "woocommerce-germanized.php");
const REPO_URL = "https://github.com/vendidero/woocommerce-germanized.git";
const REF = process.env.GERMANIZED_REF || "master";

function main() {
	if (fs.existsSync(MAIN_FILE)) {
		console.log(`Germanized already present at ${TARGET_DIR}`);
		return;
	}

	fs.mkdirSync(path.dirname(TARGET_DIR), { recursive: true });

	console.log(`Cloning Germanized (${REF}) from ${REPO_URL}`);
	execSync(
		`git clone --depth 1 --branch ${REF} ${REPO_URL} ${TARGET_DIR}`,
		{ stdio: "inherit" },
	);

	if (!fs.existsSync(MAIN_FILE)) {
		throw new Error(
			`Clone completed but ${MAIN_FILE} was not found.`,
		);
	}

	console.log(`Germanized installed to ${TARGET_DIR}`);
}

main();
