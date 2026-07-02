const { runWpCli } = require("./wp-cli");
const { getStack } = require("./stacks");

const stackId = process.env.COMPAT_STACK;
if (!stackId) {
	console.error("COMPAT_STACK is required (e.g. germanized)");
	process.exit(1);
}

const stack = getStack(stackId);

console.log(`=== Compat teardown: ${stack.label} (${stack.id}) ===`);

for (const plugin of stack.plugins) {
	runWpCli(`wp plugin deactivate ${plugin} || true`);
}

console.log(`=== Compat teardown complete: ${stack.id} ===`);
