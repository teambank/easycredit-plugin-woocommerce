const { execSync } = require("child_process");
const { getStack, getLatestStacks } = require("./stacks");

function run(command, env = {}) {
	execSync(command, {
		stdio: "inherit",
		env: { ...process.env, ...env },
	});
}

function compatProjectName(stackId) {
	return `compat:${stackId} @ Desktop Chrome`;
}

function runStack(stackId) {
	const stack = getStack(stackId);
	const project = compatProjectName(stackId);

	console.log(`\n=== Running compat stack: ${stack.label} (${stack.id}) ===`);

	run("node scripts/compat/setup.js", { COMPAT_STACK: stackId });

	try {
		run(
			`COMPAT_TESTS=1 COMPAT_STACK=${stackId} npx playwright test -c tests/ --project="${project}" --workers=1`,
			{ COMPAT_STACK: stackId, COMPAT_TESTS: "1" }
		);
	} finally {
		run("node scripts/compat/teardown.js", { COMPAT_STACK: stackId });
	}
}

const requestedStack = process.env.COMPAT_STACK;
const stacks = requestedStack ? [getStack(requestedStack)] : getLatestStacks();

if (!stacks.length) {
	console.error("No compat stacks configured.");
	process.exit(1);
}

for (const stack of stacks) {
	runStack(stack.id);
}
