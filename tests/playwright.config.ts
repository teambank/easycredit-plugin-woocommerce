import {
	PlaywrightTestConfig,
	Project,
	defineConfig,
	devices,
} from "@playwright/test";

const minutes = (min: number) => {
	return  min * 60 * 1000
}

const isBlocksCheckout  = (): boolean => {
  if (!process.env.VERSION) {
    return false
  }
  return (process.env.VERSION.localeCompare('8.3', undefined, { numeric: true, sensitivity: 'base' }) >= 0);
}

let projects: Project[] = [{ name: "backend-auth", testMatch: /.*\.setup\.ts/ }];

if (isBlocksCheckout()) {
	['Desktop Chrome', 
		//'iPhone 14'
	].forEach((device) => {
		projects.push({
			name: `blocks-checkout @ ${device}`,
			use: {
				...devices[device],
			},
			testMatch: 'checkout\.spec\.ts',
			retries: 1,
		});
	});
} else {
	["Desktop Chrome", 
		//"iPhone 14"
	].forEach((device) => {
		projects.push({
			name: `classic-checkout @ ${device}`,
			use: {
				...devices[device],
			},
			testMatch: 'classic-checkout\.spec\.ts',
			retries: 1,
		});
	});
}

/* test backend only desktop */
["Desktop Chrome"].forEach((device) => {
	let name = projects.find((p) => p.name?.match("checkout"))?.name; // checkout requiered, so that we have at least one order in the backend
	projects.push({
		name: `backend @ ${device}`,
		use: {
			...devices[device],
			storageState: "playwright/.auth/user.json",
		},
		dependencies: ["backend-auth", name as string],
		testMatch: 'backend\.spec\.ts',
		retries: 1,
	});
});

let config: PlaywrightTestConfig = {
	outputDir: "../test-results/" + process.env.VERSION + "/",
	use: {
		baseURL: process.env.BASE_URL ?? "http://localhost/",
		trace: "retain-on-failure",
		locale: "de-DE",
	},
	timeout: minutes(1),
	projects: projects,
	reporter: [
		["list", { printSteps: true }],
		["html", { outputDir: "../test-results/" + process.env.VERSION + "/" }],
		["github"],
	],
};

if (!process.env.BASE_URL) {
  config = {
    ...config,
    ... {
      webServer: {
        command: 'PHP_CLI_SERVER_WORKERS=8 sudo php -S localhost:80 -t /opt/wordpress',
        url: 'http://localhost/',
        reuseExistingServer: !process.env.CI,
        stdout: 'ignore',
        stderr: 'pipe',
        timeout: 5 * 1000
      }
    }
  }
}

export default defineConfig(config)
