const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");
const { isPluginInstalledInWp } = require("./wp-cli");

const ROOT = path.join(__dirname, "../..");

function resolveLocalPluginEntry(plugin) {
	const candidates =
		plugin === "woocommerce-german-market"
			? ["WooCommerce-German-Market.php", `${plugin}.php`]
			: [`${plugin}.php`, "WooCommerce-German-Market.php"];

	for (const file of candidates) {
		const absolute = path.join(ROOT, "src/plugins", plugin, file);
		if (fs.existsSync(absolute)) {
			return `${plugin}/${file}`;
		}
	}

	return null;
}

function getWpEnvCliContainerName() {
	const output = execSync('docker ps --filter "name=cli-1" --format "{{.Names}}"', {
		encoding: "utf8",
	});

	const names = output
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean);

	if (!names.length) {
		throw new Error("wp-env cli container not found. Run `pnpm exec wp-env start` first.");
	}

	return names[0];
}

function ensureLocalPluginInWp(plugin) {
	const pluginEntry = resolveLocalPluginEntry(plugin);
	const source = path.join(ROOT, "src/plugins", plugin);

	if (!pluginEntry || !fs.existsSync(source)) {
		return null;
	}

	if (isPluginInstalledInWp(plugin)) {
		return pluginEntry;
	}

	const container = getWpEnvCliContainerName();
	const destination = `/var/www/html/wp-content/plugins/${plugin}`;

	console.log(`Installing local plugin ${plugin} into wp-env...`);
	execSync(`docker exec ${container} rm -rf ${destination}`, { stdio: "inherit" });
	execSync(`docker cp ${source}/. ${container}:${destination}/`, {
		stdio: "inherit",
	});

	if (!isPluginInstalledInWp(plugin)) {
		throw new Error(`Failed to install local plugin ${plugin} into wp-env.`);
	}

	return pluginEntry;
}

module.exports = {
	ensureLocalPluginInWp,
	resolveLocalPluginEntry,
};
