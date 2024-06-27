import { PlaywrightTestConfig, defineConfig, devices } from '@playwright/test';

let config: PlaywrightTestConfig = {
	outputDir: "../test-results/" + process.env.VERSION + "/",
	use: {
		baseURL: process.env.BASE_URL ?? "http://localhost/",
		trace: "on",
	},
	timeout: 5 * 60 * 1000, // 5m
	projects: [
		{ name: "setup", testMatch: /.*\.setup\.ts/ },
		{
			name: "backend",
			use: {
				...devices["Desktop Chrome"],
				storageState: "playwright/.auth/user.json",
			},
			dependencies: ["setup"],
			testMatch: /backend\.spec\.ts/,
		},
		{
			name: "checkout",
			use: {
				...devices["Desktop Chrome"]
			},
			testMatch: /checkout\.spec\.ts/,
		},
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
