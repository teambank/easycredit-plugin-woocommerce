#!/usr/bin/env node

const fs = require("fs");
const path = require("path");
const readline = require("readline");
const { getMatrixIncludes } = require("./wp-env-matrix");

function printOptions(includes) {
	console.log("Available version combinations from test.yml:\n");
	includes.forEach((entry, idx) => {
		const php = entry["php-version"];
		const wp = entry["wordpress-version"];
		const woo = entry["woocommerce-version"];
		console.log(
			`${idx + 1}. PHP ${php}, WordPress ${wp}, WooCommerce ${woo}`,
		);
	});
	console.log("");
}

function parseIndexFromArgs(args, total) {
	let index = null;

	for (let i = 0; i < args.length; i++) {
		const arg = args[i];

		if (arg === "--index" || arg === "-i") {
			const next = args[i + 1];
			if (!next) {
				throw new Error("Missing value for --index");
			}
			const n = Number(next);
			if (!Number.isInteger(n) || n < 1 || n > total) {
				throw new Error(
					`Invalid index "${next}". Please choose a number between 1 and ${total}.`,
				);
			}
			index = n - 1;
			break;
		}

		// Allow simple positional numeric argument as shorthand
		if (/^\d+$/.test(arg) && index === null) {
			const n = Number(arg);
			if (!Number.isInteger(n) || n < 1 || n > total) {
				throw new Error(
					`Invalid index "${arg}". Please choose a number between 1 and ${total}.`,
				);
			}
			index = n - 1;
			break;
		}
	}

	return index;
}

function promptForIndex(total) {
	return new Promise((resolve) => {
		const rl = readline.createInterface({
			input: process.stdin,
			output: process.stdout,
		});

		rl.question(
			`Choose a combination (1-${total}) to generate .wp-env.override.json: `,
			(answer) => {
				rl.close();
				const n = Number(answer.trim());
				if (!Number.isInteger(n) || n < 1 || n > total) {
					console.error(
						`Invalid choice "${answer}". Please run the script again and choose a number between 1 and ${total}.`,
					);
					process.exit(1);
				}
				resolve(n - 1);
			},
		);
	});
}

async function main() {
	try {
		const targetPath = path.join(process.cwd(), ".wp-env.override.json");

		// Respect an existing override file
		if (fs.existsSync(targetPath)) {
			console.log(
				`.wp-env.override.json already exists, keeping existing configuration.`,
			);
			return;
		}

		const includes = getMatrixIncludes();

		const args = process.argv.slice(2);
		let index = parseIndexFromArgs(args, includes.length);

		// Non-interactive "latest" mode for automation (e.g. devcontainer, scripts)
		if (index === null) {
			const hasLatestFlag =
				args.includes("--latest") || args.includes("-l");
			if (hasLatestFlag) {
				index = includes.length - 1; // assume last is most recent
			}
		}

		if (index === null) {
			printOptions(includes);
			index = await promptForIndex(includes.length);
		}

		const chosen = includes[index];
		const php = chosen["php-version"];
		const wp = chosen["wordpress-version"];
		const woo = chosen["woocommerce-version"];

		const wpEnvConfig = {
			core: `WordPress/WordPress#${wp}`,
			phpVersion: php,
			config: {
				WOOCOMMERCE_VERSION: woo,
			},
			lifecycleScripts: {},
		};

		fs.writeFileSync(targetPath, JSON.stringify(wpEnvConfig, null, 2));

		console.log(
			`Created ${path.relative(
				process.cwd(),
				targetPath,
			)} for PHP ${php}, WordPress ${wp}, WooCommerce ${woo}.`,
		);
		console.log(
			"NOTE: Remember to set the WooCommerce version (e.g. VERSION env var) separately if needed.",
		);
	} catch (err) {
		console.error(`Error: ${err.message}`);
		process.exit(1);
	}
}

main();
