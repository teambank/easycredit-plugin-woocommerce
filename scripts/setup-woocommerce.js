const { execSync } = require("child_process");
const fs = require("fs");
const path = require("path");

// Set DEBUG=true to see verbose output
const DEBUG = process.env.DEBUG === "true";

const run = (cmd, options = {}) => {
	if (DEBUG) {
		console.log(`[DEBUG] Executing: ${cmd}`);
	}
	try {
		// Use sh -c to wrap the command, which should preserve all flags including --version
		// This prevents wp-env from parsing the command and stripping flags
		const fullCmd = `npm exec wp-env run cli -- sh -c '${cmd.replace(/'/g, "'\\''")}'`;
		const output = execSync(fullCmd, {
			stdio: options.stdio || (DEBUG ? "inherit" : "pipe"),
			encoding: "utf8",
		});
		if (!DEBUG && output) {
			console.log(output.toString());
		}
		return output;
	} catch (error) {
		console.error(`[ERROR] Command failed: ${cmd}`);
		console.error(`[ERROR] ${error.message}`);
		if (error.stdout) console.error(`[ERROR] stdout: ${error.stdout}`);
		if (error.stderr) console.error(`[ERROR] stderr: ${error.stderr}`);
		throw error;
	}
};
const VERSION = process.env.VERSION;
console.log(`=== Setup WooCommerce @ ${VERSION} ===`);
if (DEBUG) {
	console.log("[DEBUG] Debug mode enabled");
	console.log(`[DEBUG] Working directory: ${process.cwd()}`);
	console.log(`[DEBUG] Script directory: ${__dirname}`);
	console.log(`[DEBUG] WooCommerce version: ${VERSION}`);
}
try {
	// Install and activate WooCommerce plugin
	run(
		`wp plugin install woocommerce --version=${VERSION} --activate --force`
	);
	run("wp plugin activate wc-easycredit");

	// Install and activate Storefront theme
	run("wp theme install storefront --activate");

	// Install German language packs
	run("wp language core install de_DE");
	run("wp language theme install storefront de_DE");
	run("wp language plugin install woocommerce de_DE");

	// Switch site language to German
	run("wp site switch-language de_DE");

	// Update WooCommerce settings and options
	run(
		"wp option update woocommerce_onboarding_profile '{\"skipped\": true}' --json"
	);
	run("wp option update woocommerce_coming_soon no");
	run('wp option update woocommerce_task_list_reminder_bar_hidden "yes"');
	run("wp option update woocommerce_task_list_prompt_shown 1");
	run('wp option update woocommerce_show_marketplace_suggestions "no"');
	run('wp option update woocommerce_allow_tracking "no"');
	run('wp option update woocommerce_task_list_complete "yes"');
	run('wp option update woocommerce_task_list_welcome_modal_dismissed "yes"');
	run('wp option update woocommerce_default_country "DE:DE-BY"');
	run('wp option update woocommerce_currency "EUR"');
	run('wp option update woocommerce_checkout_phone_field "optional"');
	run('wp option update woocommerce_calc_shipping "yes"');

	// Configure flat rate shipping method "Shipping" with price 5.90 in default zone (zone 0)
	const SHIPPING_METHOD_ID = run(
		'wp wc shipping_zone_method create 0 --method_id="flat_rate" --user="admin" --porcelain'
	)
		.toString()
		.trim();
	if (SHIPPING_METHOD_ID) {
		run(
			`wp option update woocommerce_flat_rate_${SHIPPING_METHOD_ID}_settings '{"title":"Shipping","tax_status":"taxable","cost":"5.90"}'`
		);
	}

	run('wp rewrite structure "/index.php/%postname%/"');

	// Copy .htaccess file
	if (DEBUG) {
		console.log("[DEBUG] Copying .htaccess file...");
	}
	const htaccessPath = path.join(__dirname, ".htaccess");
	if (!fs.existsSync(htaccessPath)) {
		throw new Error(`.htaccess file not found at: ${htaccessPath}`);
	}
	const htaccessContent = fs.readFileSync(htaccessPath, "utf8");
	if (DEBUG) {
		console.log(
			`[DEBUG] .htaccess content length: ${htaccessContent.length} bytes`
		);
	}
	try {
		execSync(`npm exec wp-env run cli -- sh -c 'cat > .htaccess'`, {
			input: htaccessContent,
			stdio: DEBUG ? "inherit" : "pipe",
			encoding: "utf8",
		});
		console.log("✓ Copied .htaccess file");
	} catch (error) {
		console.error(`[ERROR] Failed to copy .htaccess: ${error.message}`);
		throw error;
	}

	// Create simple products
	run(
		'wp wc product create --name="Regular" --slug="regular" --type="simple" --sku="regular" --regular_price="201" --status="publish" --user="admin"'
	);
	run(
		'wp wc product create --name="Below50" --slug="below50" --type="simple" --sku="below50" --regular_price="49" --status="publish" --user="admin"'
	);
	run(
		'wp wc product create --name="Below200" --slug="below200" --type="simple" --sku="below200" --regular_price="199" --status="publish" --user="admin"'
	);
	run(
		'wp wc product create --name="Above5000" --slug="above5000" --type="simple" --sku="above5000" --regular_price="6000" --status="publish" --user="admin"'
	);
	run(
		'wp wc product create --name="Above10000" --slug="above10000" --type="simple" --sku="above10000" --regular_price="11000" --status="publish" --user="admin"'
	);

	// Create a variable product
	const PID = run(
		'wp wc product create --name="Variable" --slug="variable" --type="variable" --sku="variable" --status="publish" --user="admin" --attributes=\'[{"name": "Size", "options": ["small", "medium", "large"], "visible": true, "variation": true}]\' --porcelain'
	)
		.toString()
		.trim();

	// Check if PID is not empty and create variations
	if (PID) {
		run(
			`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "small"}]' --regular_price="201" --user="admin"`
		);
		run(
			`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "medium"}]' --regular_price="21" --user="admin"`
		);
		run(
			`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "large"}]' --regular_price="21" --user="admin"`
		);
	}
	console.log("=== Setup completed successfully ===");
} catch (error) {
	console.error(`=== Setup failed ===`);
	console.error(`Error: ${error.message}`);
	if (error.stack && DEBUG) {
		console.error(`Stack trace: ${error.stack}`);
	}
	process.exit(1);
}
