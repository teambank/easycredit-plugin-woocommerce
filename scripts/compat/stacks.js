const fs = require("fs");
const path = require("path");

const STACKS_PATH = path.join(__dirname, "../../tests/compat/stacks.json");

function loadStacks() {
	const config = JSON.parse(fs.readFileSync(STACKS_PATH, "utf8"));
	return config.stacks;
}

function getStack(stackId) {
	const stack = loadStacks().find((entry) => entry.id === stackId);
	if (!stack) {
		const available = loadStacks()
			.map((entry) => entry.id)
			.join(", ");
		throw new Error(`Unknown compat stack "${stackId}". Available: ${available}`);
	}
	return stack;
}

function getLatestStacks() {
	return loadStacks().filter((stack) => stack.runOnLatest);
}

module.exports = { loadStacks, getStack, getLatestStacks, STACKS_PATH };
