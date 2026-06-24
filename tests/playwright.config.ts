import {
	PlaywrightTestConfig,
	Project,
	defineConfig,
	devices,
} from "@playwright/test";
import { seconds } from "./helpers/utils";

const isBlocksCheckout = (): boolean => {
	if (!process.env.VERSION) {
		return false;
	}
	return (
		process.env.VERSION.localeCompare("8.3", undefined, {
			numeric: true,
			sensitivity: "base",
		}) >= 0
	);
};

let projects: Project[] = [
	{ name: "backend-auth", testMatch: "setup/backend-auth.setup.ts" },
];

if (isBlocksCheckout()) {
	["Desktop Chrome"].forEach((device) => {
		projects.push({
			name: `blocks-checkout @ ${device}`,
			use: {
				...devices[device],
			},
			testMatch: "specs/checkout.spec.ts",
		});
	});
} else {
	["Desktop Chrome"].forEach((device) => {
		projects.push({
			name: `classic-checkout @ ${device}`,
			use: {
				...devices[device],
			},
			testMatch: "specs/classic-checkout.spec.ts",
		});
	});
}

["Desktop Chrome"].forEach((device) => {
	projects.push({
		name: `frontend @ ${device}`,
		use: {
			...devices[device],
		},
		testMatch: "specs/frontend.spec.ts",
	});
});

let config: PlaywrightTestConfig = {
	outputDir: "../test-results/" + process.env.VERSION + "/",
	use: {
		baseURL: process.env.BASE_URL ?? "http://localhost/",
		trace: "retain-on-failure",
		locale: "de-DE",
	},
	retries: process.env.CI ? 2 : 0,
	timeout: seconds(60),
	expect: { timeout: 10_000 },
	projects: projects,
	reporter: [["list", { printSteps: true }], ["html"]],
};

if (!process.env.BASE_URL) {
	config = {
		...config,
		...{
			webServer: {
				command:
					"PHP_CLI_SERVER_WORKERS=8 sudo php -S localhost:80 -t /opt/wordpress",
				url: "http://localhost/",
				reuseExistingServer: !process.env.CI,
				stdout: "ignore",
				stderr: "pipe",
				timeout: 10 * 1000,
			},
		},
	};
}

export default defineConfig(config);
