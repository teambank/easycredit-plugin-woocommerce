const { runWpCli, isPluginInstalledInWp } = require("./wp-cli");
const { getStack } = require("./stacks");
const { setupGermanizedLegal } = require("./setup-germanized-legal");
const { setupGermanMarketLegal } = require("./setup-german-market-legal");
const { ensureLocalPluginInWp } = require("./ensure-local-plugin");

const stackId = process.env.COMPAT_STACK;
if (!stackId) {
	console.error("COMPAT_STACK is required (e.g. germanized)");
	process.exit(1);
}

const stack = getStack(stackId);

console.log(`=== Compat setup: ${stack.label} (${stack.id}) ===`);

if (stack.id === "german-market") {
	for (const plugin of ["woocommerce-germanized", "woocommerce-germanized-pro"]) {
		try {
			runWpCli(`wp plugin deactivate ${plugin}`, { capture: true });
		} catch {
			// Plugin not installed.
		}
		try {
			runWpCli(`wp plugin uninstall ${plugin} --deactivate`, { capture: true });
		} catch {
			// Plugin not installed.
		}
	}
}

let germanizedActivated = false;

for (const plugin of stack.localPlugins || []) {
	const pluginEntry = ensureLocalPluginInWp(plugin);
	if (!pluginEntry) {
		continue;
	}

	runWpCli(`wp plugin activate ${pluginEntry}`);
	germanizedActivated = plugin === "woocommerce-germanized" || germanizedActivated;
}

if (!germanizedActivated) {
	for (const plugin of stack.plugins || []) {
		runWpCli(`wp plugin install ${plugin} --activate --force`);
	}

	const installedSlug = runWpCli(
		`wp plugin list --status=active --field=name | grep -i germanized | head -1`,
		{ capture: true },
	).trim();
	if (installedSlug) {
		germanizedActivated = true;
	}
}

for (const [option, value] of Object.entries(stack.options || {})) {
	const escaped = String(value).replace(/"/g, '\\"');
	runWpCli(`wp option update ${option} "${escaped}"`);
}

if (stack.setupGermanizedLegal) {
	setupGermanizedLegal();
}

if (stack.setupGermanMarketLegal) {
	const hasGermanMarket = (stack.localPlugins || []).some((plugin) =>
		isPluginInstalledInWp(plugin),
	);
	if (!hasGermanMarket) {
		throw new Error(
			"German Market compat tests require woocommerce-german-market in src/plugins/.",
		);
	}
	setupGermanMarketLegal();
}

console.log(`=== Compat setup complete: ${stack.id} ===`);
