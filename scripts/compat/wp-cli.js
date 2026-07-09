const { execSync } = require("child_process");

function parseWpCliOutput(output) {
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
			line.startsWith("Deprecated:") ||
			line.startsWith("Success:")
		) {
			continue;
		}

		return line;
	}

	return "";
}

function runWpCli(cmd, { capture = false } = {}) {
	const output = execSync(
		`npm exec wp-env run cli -- sh -c '${cmd.replace(/'/g, "'\\''")}'`,
		{
			stdio: capture ? "pipe" : "inherit",
			encoding: "utf8",
		},
	);

	return capture ? parseWpCliOutput(output) : output;
}

function isPluginInstalledInWp(slug) {
	try {
		runWpCli(`wp plugin is-installed ${slug}`, { capture: true });
		return true;
	} catch {
		return false;
	}
}

module.exports = { runWpCli, parseWpCliOutput, isPluginInstalledInWp };
