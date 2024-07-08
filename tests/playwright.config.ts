import {
	PlaywrightTestConfig,
	Project,
	defineConfig,
	devices,
} from "@playwright/test";
import { seconds } from './utils'

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
			testMatch: 'checkout\.spec\.ts'
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
			testMatch: 'classic-checkout\.spec\.ts'
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
		testMatch: 'backend\.spec\.ts'
	});
});

let config: PlaywrightTestConfig = {
	outputDir: "../test-results/" + process.env.VERSION + "/",
	use: {
		baseURL: process.env.BASE_URL ?? "http://localhost/",
		trace: "retain-on-failure",
		locale: "de-DE",
	},
//	retries: 0,
	timeout: seconds(30),
	projects: projects,
	reporter: [
		["list", { printSteps: true }],
		["html", { outputFolder: "../test-results/html/" + process.env.VERSION + "/" }],
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
