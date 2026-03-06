import { test, expect } from "@playwright/test";

/**
 * For E2E tests we use Playwright's route interception to mock API responses.
 * This keeps tests fast and deterministic — no real Laravel server required.
 *
 * Pattern:
 *   await page.route('/api/...', route => route.fulfill({ json: { ... } }))
 */

test.describe("Home page", () => {
  test("renders hero and brand name", async ({ page }) => {
    // Mock the featured stores endpoint
    await page.route("**/api/v1/stores*", (route) =>
      route.fulfill({
        json: {
          data: [
            {
              id: 1,
              name: "Aling Nena Foods",
              slug: "aling-nena-foods",
              sector: "Food & Beverage",
              logo_url: null,
            },
            {
              id: 2,
              name: "Tindahan ni Mang Jose",
              slug: "tindahan-ni-mang-jose",
              sector: "Retail",
              logo_url: null,
            },
          ],
        },
      }),
    );
    // Mock /api/v1/user so the auth guard doesn't redirect
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );

    await page.goto("/");

    await expect(page.getByRole("link", { name: "NegosyoHub" })).toBeVisible();
    await expect(page.getByText("The Philippine Marketplace")).toBeVisible();
    await expect(page.getByText("Explore Now")).toBeVisible();
  });

  test("featured stores are displayed", async ({ page }) => {
    await page.route("**/api/v1/stores*", (route) =>
      route.fulfill({
        json: {
          data: [
            {
              id: 1,
              name: "Aling Nena Foods",
              slug: "aling-nena-foods",
              sector: "Food & Beverage",
              logo_url: null,
            },
          ],
        },
      }),
    );
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );

    await page.goto("/");
    await expect(page.getByText("Aling Nena Foods")).toBeVisible();
  });
});

test.describe("Stores listing page", () => {
  test("renders list of stores", async ({ page }) => {
    await page.route("**/api/v1/stores*", (route) =>
      route.fulfill({
        json: {
          data: [
            {
              id: 1,
              name: "Pizza Palace",
              slug: "pizza-palace",
              sector: "Food & Beverage",
              logo_url: null,
            },
            {
              id: 2,
              name: "Burger Barn",
              slug: "burger-barn",
              sector: "Food & Beverage",
              logo_url: null,
            },
          ],
        },
      }),
    );
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );

    await page.goto("/stores");

    await expect(page.getByText("Browse Stores")).toBeVisible();
    await expect(page.getByText("Pizza Palace")).toBeVisible();
    await expect(page.getByText("Burger Barn")).toBeVisible();
  });

  test("search form is present", async ({ page }) => {
    await page.route("**/api/v1/stores*", (route) =>
      route.fulfill({ json: { data: [] } }),
    );
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );

    await page.goto("/stores");

    await expect(page.getByPlaceholder("Search stores…")).toBeVisible();
  });
});

test.describe("404 page", () => {
  test("shows not found for unknown route", async ({ page }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.goto("/this-route-does-not-exist-at-all");
    await expect(page.getByText("404")).toBeVisible();
  });
});
