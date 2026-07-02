const { runWpCli } = require("./wp-cli");
const { getStack } = require("./stacks");

const stackId = process.env.COMPAT_STACK;
if (!stackId) {
	console.error("COMPAT_STACK is required (e.g. germanized)");
	process.exit(1);
}

const stack = getStack(stackId);

console.log(`=== Compat setup: ${stack.label} (${stack.id}) ===`);

for (const plugin of stack.plugins) {
	runWpCli(`wp plugin install ${plugin} --activate --force`);
}

for (const [option, value] of Object.entries(stack.options || {})) {
	const escaped = String(value).replace(/"/g, '\\"');
	runWpCli(`wp option update ${option} "${escaped}"`);
}

console.log(`=== Compat setup complete: ${stack.id} ===`);
