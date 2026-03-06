import { test, expect } from "@playwright/test";

test.describe("Login page", () => {
  test.beforeEach(async ({ page }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.route("**/sanctum/csrf-cookie", (route) =>
      route.fulfill({ status: 204 }),
    );
  });

  test("renders login form", async ({ page }) => {
    await page.goto("/login");

    await expect(
      page.getByRole("heading", { name: "Welcome back" }),
    ).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
    await expect(page.getByLabel("Password", { exact: true })).toBeVisible();
  });

  test("shows error on failed login", async ({ page }) => {
    await page.route("**/api/v1/login", (route) =>
      route.fulfill({
        status: 422,
        json: { message: "Invalid credentials." },
      }),
    );

    await page.goto("/login");
    await page.getByLabel("Email").fill("bad@example.com");
    await page.getByLabel("Password", { exact: true }).fill("wrongpassword");
    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page.getByText("Invalid credentials.")).toBeVisible();
  });

  test("redirects to home on successful login", async ({ page }) => {
    await page.route("**/api/v1/login", (route) =>
      route.fulfill({
        json: {
          token: "tok_abc",
          user: { id: 1, name: "Juan", role: "customer" },
        },
      }),
    );

    await page.goto("/login");
    await page.getByLabel("Email").fill("juan@example.com");
    await page.getByLabel("Password", { exact: true }).fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL("/");
  });
});

test.describe("Register page", () => {
  test.beforeEach(async ({ page }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.route("**/sanctum/csrf-cookie", (route) =>
      route.fulfill({ status: 204 }),
    );
  });

  test("renders registration form", async ({ page }) => {
    await page.goto("/register");

    await expect(
      page.getByRole("heading", { name: "Create your account" }),
    ).toBeVisible();
    await expect(page.getByLabel("Full Name")).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
  });

  test("shows validation errors from API", async ({ page }) => {
    await page.route("**/api/v1/register", (route) =>
      route.fulfill({
        status: 422,
        json: {
          errors: {
            email: ["The email has already been taken."],
          },
        },
      }),
    );

    await page.goto("/register");
    await page.getByLabel("Full Name").fill("Maria Santos");
    await page.getByLabel("Email").fill("taken@example.com");
    await page.getByLabel("Password", { exact: true }).fill("password123");
    await page.getByLabel("Confirm Password").fill("password123");
    await page.getByRole("button", { name: "Create Account" }).click();

    await expect(
      page.getByText("The email has already been taken."),
    ).toBeVisible();
  });
});

test.describe("Auth guards", () => {
  test("redirects unauthenticated user from /checkout to /login", async ({
    page,
  }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.goto("/checkout");
    await expect(page).toHaveURL(/\/login/);
  });

  test("redirects unauthenticated user from /account/orders to /login", async ({
    page,
  }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ status: 401, json: {} }),
    );
    await page.goto("/account/orders");
    await expect(page).toHaveURL(/\/login/);
  });

  test("redirects authenticated user away from /login to home", async ({
    page,
  }) => {
    await page.route("**/api/v1/user", (route) =>
      route.fulfill({ json: { id: 1, name: "Juan", role: "customer" } }),
    );
    // Prevent cart fetch from triggering auth:unauthenticated after redirect to /
    await page.route("**/api/v1/cart", (route) =>
      route.fulfill({
        json: { lines: [], total: { formatted: "₱0.00", value: 0 } },
      }),
    );

    await page.goto("/login");
    await expect(page).toHaveURL("/");
  });
});
