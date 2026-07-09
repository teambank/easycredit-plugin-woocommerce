const fs = require("fs");
const path = require("path");
const { runWpCli, isPluginInstalledInWp } = require("./wp-cli");
const { getStack } = require("./stacks");
const { setupGermanizedLegal } = require("./setup-germanized-legal");

const ROOT = path.join(__dirname, "../..");

const stackId = process.env.COMPAT_STACK;
if (!stackId) {
	console.error("COMPAT_STACK is required (e.g. germanized)");
	process.exit(1);
}

const stack = getStack(stackId);

console.log(`=== Compat setup: ${stack.label} (${stack.id}) ===`);

let germanizedActivated = false;

for (const plugin of stack.localPlugins || []) {
	const pluginFile = path.join(
		ROOT,
		`src/plugins/${plugin}/${plugin}.php`,
	);
	if (!fs.existsSync(pluginFile) || !isPluginInstalledInWp(plugin)) {
		continue;
	}

	runWpCli(`wp plugin activate ${plugin}`);
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

console.log(`=== Compat setup complete: ${stack.id} ===`);
