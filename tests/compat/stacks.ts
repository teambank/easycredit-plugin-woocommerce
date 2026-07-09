import fs from "fs";
import path from "path";

export type CompatStack = {
	id: string;
	label: string;
	runOnLatest: boolean;
	plugins?: string[];
	localPlugins?: string[];
	setupGermanizedLegal?: boolean;
	options?: Record<string, string>;
	spec: string;
};

type CompatStacksFile = {
	stacks: CompatStack[];
};

const stacksPath = path.join(__dirname, "stacks.json");

export function loadCompatStacks(): CompatStack[] {
	const raw = fs.readFileSync(stacksPath, "utf8");
	const config = JSON.parse(raw) as CompatStacksFile;
	return config.stacks;
}

export function getCompatStack(stackId: string): CompatStack {
	const stack = loadCompatStacks().find((entry) => entry.id === stackId);
	if (!stack) {
		throw new Error(`Unknown compat stack: ${stackId}`);
	}
	return stack;
}

export function compatProjectName(stackId: string, device = "Desktop Chrome"): string {
	return `compat:${stackId} @ ${device}`;
}
