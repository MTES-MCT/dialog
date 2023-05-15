// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// This port is specific to the web server configured below for E2E testing.
const PORT = 8001;

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
    testDir: './tests/e2e',
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: 'html',
    use: {
        baseURL: `http://localhost:${PORT}`,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.js/ },
        {
            name: 'desktop-firefox',
            use: { ...devices['Desktop Firefox'] },
            testIgnore: /.*\.mobile\.spec\.js/,
            dependencies: ['setup'],
        },
        {
            name: 'mobile-chromium',
            use: { ...devices['Pixel 5'] },
            testMatch: /.*\.mobile\.spec\.js/,
            dependencies: ['setup'],
        },
    ],
    webServer: {
        command: `symfony server:start --port=${PORT}`,
        env: {
            APP_ENV: 'test',
        },
        url: `http://localhost:${PORT}`,
    },
});
