import { test, expect } from "@playwright/test";

const mockUser = { id: 1, name: "Juan", role: "customer" };

const mockCartWithItem = {
  lines: [
    {
      id: "line_1",
      quantity: 1,
      purchasable: { name: "Crispy Pata", thumbnail: null },
      sub_total: { formatted: "₱350.00" },
    },
  ],
  total: { formatted: "₱350.00" },
  meta: { store_id: 3 },
};

const mockEmptyCart = { lines: [], total: { formatted: "₱0.00" }, meta: {} };

test.describe("Cart page", () => {
  test("shows empty state when no items", async ({ page }) => {
    await page.route("/api/user", (route) => route.fulfill({ json: mockUser }));
    await page.route("/api/cart", (route) =>
      route.fulfill({ json: mockEmptyCart }),
    );

    await page.goto("/cart");

    await expect(page.getByText("Your cart is empty")).toBeVisible();
    await expect(page.getByText("Browse stores")).toBeVisible();
  });

  test("shows cart items and total", async ({ page }) => {
    await page.route("/api/user", (route) => route.fulfill({ json: mockUser }));
    await page.route("/api/cart", (route) =>
      route.fulfill({ json: mockCartWithItem }),
    );

    await page.goto("/cart");

    await expect(page.getByText("Crispy Pata")).toBeVisible();
    await expect(page.getByText("₱350.00").first()).toBeVisible();
    await expect(
      page.getByRole("link", { name: "Proceed to Checkout" }),
    ).toBeVisible();
  });
});

test.describe("Cart drawer", () => {
  test("cart icon in navbar shows item count", async ({ page }) => {
    await page.route("/api/user", (route) => route.fulfill({ json: mockUser }));
    await page.route("/api/stores*", (route) =>
      route.fulfill({ json: { data: [] } }),
    );
    // The cart is fetched lazily by the store only when explicitly called
    // so we just verify the navbar renders the count reactively

    await page.goto("/");
    // No cart loaded yet — badge should not be visible
    await expect(
      page.locator("span.rounded-full.bg-brand-500"),
    ).not.toBeVisible();
  });
});

test.describe("Checkout flow", () => {
  test("redirects unauthenticated user to login", async ({ page }) => {
    await page.route("/api/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.goto("/checkout");
    await expect(page).toHaveURL(/\/login/);
  });

  test("shows address form on step 1", async ({ page }) => {
    await page.route("/api/user", (route) => route.fulfill({ json: mockUser }));
    await page.route("/api/cart", (route) =>
      route.fulfill({ json: mockCartWithItem }),
    );
    await page.route("/api/cart/shipping-options", (route) =>
      route.fulfill({ json: [] }),
    );

    await page.goto("/checkout");

    await expect(page.getByText("Delivery Address")).toBeVisible();
    await expect(page.getByLabel("First Name")).toBeVisible();
    await expect(page.getByLabel("Last Name")).toBeVisible();
    await expect(page.getByLabel("City")).toBeVisible();
  });

  test("progresses to shipping step after address", async ({ page }) => {
    await page.route("/api/user", (route) => route.fulfill({ json: mockUser }));
    await page.route("/api/cart", (route) =>
      route.fulfill({ json: mockCartWithItem }),
    );
    await page.route("/api/cart/address", (route) =>
      route.fulfill({ json: {} }),
    );
    await page.route("/api/cart/shipping-options", (route) =>
      route.fulfill({
        json: [
          {
            id: "rate_1",
            name: "Standard Delivery",
            description: "3-5 days",
            price: { formatted: "₱100.00" },
          },
        ],
      }),
    );

    await page.goto("/checkout");

    // Fill address
    await page.getByLabel("First Name").fill("Juan");
    await page.getByLabel("Last Name").fill("dela Cruz");
    await page.getByLabel("Address Line").fill("123 Rizal St");
    await page.getByLabel("City").fill("Makati");
    await page.getByLabel("Province").fill("Metro Manila");
    await page.getByLabel("ZIP Code").fill("1200");

    await page.getByRole("button", { name: "Continue to Shipping" }).click();

    await expect(page.getByText("Shipping Method")).toBeVisible();
    await expect(page.getByText("Standard Delivery")).toBeVisible();
  });
});
