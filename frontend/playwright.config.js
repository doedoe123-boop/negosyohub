import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,

  reporter: [
    ["list"],
    ["html", { outputFolder: "tests/e2e/reports", open: "never" }],
  ],

  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL ?? "http://localhost:5173",
    trace: "on-first-retry",
    screenshot: "only-on-failure",

    // Mock all API calls so E2E tests don't need a running Laravel server
    // Override per-test when you do want real network calls
  },

  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
    {
      name: "Mobile Chrome",
      use: { ...devices["Pixel 5"] },
    },
  ],

  // Spin up the Vite dev server automatically before running tests
  webServer: {
    command: "npm run dev",
    url: "http://localhost:5173/index.html",
    reuseExistingServer: !process.env.CI,
    timeout: 60000,
  },
});
