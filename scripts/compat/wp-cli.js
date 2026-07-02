const { execSync } = require("child_process");

function runWpCli(cmd) {
	execSync(`npm exec wp-env run cli -- sh -c '${cmd.replace(/'/g, "'\\''")}'`, {
		stdio: "inherit",
		encoding: "utf8",
	});
}

module.exports = { runWpCli };
