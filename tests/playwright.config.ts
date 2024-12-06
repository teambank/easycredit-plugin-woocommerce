import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  outputDir: '../test-results/'+ process.env.VERSION + '/',
  use: {
    baseURL: process.env.BASE_URL ?? 'http://localhost/',
    trace: "retain-on-failure",
    trace: 'on'
  },
  timeout: 40 * 1000, // 40s
  retries: process.env.CI ? 2 : 0,
  reporter: [["list", { printSteps: true }], ["html"]],
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ],
  webServer: {
    command: 'PHP_CLI_SERVER_WORKERS=8 sudo php -S localhost:80 -t /opt/wordpress',
    url: 'http://localhost/',
    reuseExistingServer: !process.env.CI,
    stdout: 'ignore',
    stderr: 'pipe',
    timeout: 5 * 1000
  },
});
