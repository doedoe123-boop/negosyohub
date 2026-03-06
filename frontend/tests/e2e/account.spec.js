import { test, expect } from "@playwright/test";

// ---------------------------------------------------------------------------
// Shared fixtures
// ---------------------------------------------------------------------------

const mockUser = {
  id: 1,
  name: "Juan dela Cruz",
  email: "juan@example.com",
  phone: "09171234567",
  role: "customer",
  notification_preferences: { order_updates: true, promotions: false },
};

const mockAddresses = [
  {
    id: 1,
    label: "Home",
    line1: "123 Rizal St",
    line2: null,
    barangay: "Poblacion",
    city: "Makati",
    province: "Metro Manila",
    postal_code: "1210",
    is_default: true,
  },
  {
    id: 2,
    label: "Office",
    line1: "456 Ayala Ave",
    line2: "Unit 5B",
    barangay: "Bel-Air",
    city: "Makati",
    province: "Metro Manila",
    postal_code: "1226",
    is_default: false,
  },
];

const mockPaymentMethods = [
  {
    id: 1,
    brand: "visa",
    last4: "4242",
    exp_month: 12,
    exp_year: 2027,
    is_default: true,
  },
  {
    id: 2,
    brand: "mastercard",
    last4: "5555",
    exp_month: 6,
    exp_year: 2026,
    is_default: false,
  },
];

const mockOrders = {
  data: [
    {
      id: "ORD-001",
      status: "delivered",
      payment_status: "paid",
      total: { formatted: "₱480.00" },
      lines: [],
      created_at: "2026-03-01",
    },
    {
      id: "ORD-002",
      status: "pending",
      payment_status: "pending",
      total: { formatted: "₱180.00" },
      lines: [
        {
          id: "l2",
          description: "Sinigang",
          quantity: 1,
          purchasable_id: 99,
          sub_total: { formatted: "₱180.00" },
        },
      ],
      created_at: "2026-03-05",
    },
  ],
};

const mockOrderPending = {
  id: "ORD-002",
  status: "pending",
  payment_status: "pending",
  total: { formatted: "₱180.00" },
  lines: [
    {
      id: "l2",
      description: "Sinigang",
      quantity: 1,
      purchasable_id: 99,
      sub_total: { formatted: "₱180.00" },
    },
  ],
};

// Mock GET /api/v1/user → authenticated
async function withAuth(page) {
  await page.route("**/api/v1/user", (route) => {
    if (route.request().method() === "GET") {
      route.fulfill({ json: mockUser });
    } else {
      route.fallback();
    }
  });
  // Prevent cart fetch from triggering auth:unauthenticated redirect
  await page.route("**/api/v1/cart", (route) => {
    if (route.request().method() === "GET") {
      route.fulfill({
        json: { lines: [], total: { formatted: "₱0.00", value: 0 } },
      });
    } else {
      route.fallback();
    }
  });
}

// Mock GET /api/v1/user → 401 unauthenticated
async function withUnauth(page) {
  await page.route("**/api/v1/user", (route) =>
    route.fulfill({ status: 401, json: {} }),
  );
}

// ---------------------------------------------------------------------------
// Guard: unauthenticated users are redirected to /login
// ---------------------------------------------------------------------------

test.describe("Account route guard", () => {
  const protectedRoutes = [
    "/account",
    "/account/orders",
    "/account/addresses",
    "/account/payment-methods",
    "/account/profile",
    "/account/password",
    "/account/settings",
  ];

  for (const path of protectedRoutes) {
    test(`redirects ${path} to /login when unauthenticated`, async ({
      page,
    }) => {
      await withUnauth(page);
      await page.goto(path);
      await expect(page).toHaveURL(/\/login/);
    });
  }
});

// ---------------------------------------------------------------------------
// AccountLayout – sidebar & navigation
// ---------------------------------------------------------------------------

test.describe("AccountLayout sidebar", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: mockOrders }),
    );
    // Desktop viewport so the sidebar is visible
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto("/account");
  });

  test("shows user name, email and phone in sidebar", async ({ page }) => {
    await expect(page.getByText("Juan dela Cruz").first()).toBeVisible();
    await expect(page.getByText("juan@example.com").first()).toBeVisible();
    await expect(page.getByText("09171234567").first()).toBeVisible();
  });

  test("sidebar nav links are all present", async ({ page }) => {
    const labels = [
      "Overview",
      "My Orders",
      "Addresses",
      "Payment Methods",
      "Profile",
      "Password",
      "Settings",
    ];
    for (const label of labels) {
      await expect(
        page.getByRole("link", { name: label }).first(),
      ).toBeVisible();
    }
  });

  test("Overview link is active on /account", async ({ page }) => {
    await expect(
      page.getByRole("link", { name: "Overview" }).first(),
    ).toHaveClass(/bg-brand-50/);
  });

  test("navigating to Addresses highlights that nav item", async ({ page }) => {
    await page.route("**/api/v1/user/addresses", (route) =>
      route.fulfill({ json: mockAddresses }),
    );
    await page.getByRole("link", { name: "Addresses" }).first().click();
    await expect(page).toHaveURL("/account/addresses");
    await expect(
      page.getByRole("link", { name: "Addresses" }).first(),
    ).toHaveClass(/bg-brand-50/);
  });
});

// ---------------------------------------------------------------------------
// Account Dashboard (/account)
// ---------------------------------------------------------------------------

test.describe("Account Dashboard", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: mockOrders }),
    );
    await page.goto("/account");
  });

  test("greets user by name with email and phone", async ({ page }) => {
    await expect(
      page.getByRole("heading", { name: "Juan dela Cruz" }),
    ).toBeVisible();
    // Use .last() to get AccountLayout's inner <main>, excluding the hidden sidebar
    const content = page.locator("main").last();
    await expect(content.getByText("juan@example.com").first()).toBeVisible();
    await expect(content.getByText("09171234567").first()).toBeVisible();
  });

  test("renders all five quick-link cards", async ({ page }) => {
    await expect(
      page.getByRole("link", { name: /My Orders/ }).first(),
    ).toBeVisible();
    await expect(
      page.getByRole("link", { name: /^Addresses/ }).first(),
    ).toBeVisible();
    await expect(
      page.getByRole("link", { name: /Payment Methods/ }).first(),
    ).toBeVisible();
    await expect(page.getByRole("link", { name: /My Profile/ })).toBeVisible();
    await expect(
      page.getByRole("link", { name: /^Settings/ }).first(),
    ).toBeVisible();
  });

  test("shows recent orders section with Order IDs and status badges", async ({
    page,
  }) => {
    await expect(page.getByText("Recent Orders")).toBeVisible();
    await expect(page.getByText("Order #ORD-001")).toBeVisible();
    await expect(page.getByText("Order #ORD-002")).toBeVisible();
    await expect(page.getByText("delivered")).toBeVisible();
    await expect(page.getByText("pending").first()).toBeVisible();
  });

  test("shows empty state when no recent orders exist", async ({ page }) => {
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: { data: [] } }),
    );
    await page.goto("/account");
    await expect(page.getByText("No orders yet")).toBeVisible();
  });

  test("View all link navigates to /account/orders", async ({ page }) => {
    await page.getByRole("link", { name: /View all/i }).click();
    await expect(page).toHaveURL("/account/orders");
  });

  test("recent order row navigates to order detail page", async ({ page }) => {
    await page.route("**/api/v1/orders/ORD-001", (route) =>
      route.fulfill({
        json: { ...mockOrderPending, id: "ORD-001", status: "delivered" },
      }),
    );
    await page.getByText("Order #ORD-001").click();
    await expect(page).toHaveURL("/account/orders/ORD-001");
  });
});

// ---------------------------------------------------------------------------
// Profile page (/account/profile)
// ---------------------------------------------------------------------------

test.describe("Profile page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.goto("/account/profile");
  });

  test("pre-fills form with current user data", async ({ page }) => {
    await expect(page.getByLabel("Full Name")).toHaveValue("Juan dela Cruz");
    await expect(page.getByLabel("Phone Number")).toHaveValue("09171234567");
    await expect(page.getByLabel("Email Address")).toHaveValue(
      "juan@example.com",
    );
  });

  test("email field is read-only", async ({ page }) => {
    await expect(page.getByLabel("Email Address")).toBeDisabled();
  });

  test("shows success toast after saving profile", async ({ page }) => {
    await page.route("**/api/v1/user", (route) => {
      if (route.request().method() === "PATCH") {
        route.fulfill({ json: { ...mockUser, name: "Juan Updated" } });
      } else {
        route.fallback();
      }
    });

    await page.getByLabel("Full Name").fill("Juan Updated");
    await page.getByRole("button", { name: "Save Changes" }).click();

    await expect(page.getByText("Profile updated successfully")).toBeVisible();
  });

  test("shows error message on API failure", async ({ page }) => {
    await page.route("**/api/v1/user", (route) => {
      if (route.request().method() === "PATCH") {
        route.fulfill({
          status: 422,
          json: { message: "The name field is required." },
        });
      } else {
        route.fallback();
      }
    });

    // Clear the required field and submit via JS to bypass HTML5 validation
    await page.getByLabel("Full Name").fill("");
    await page.evaluate(() =>
      document
        .querySelector("form")
        ?.dispatchEvent(new Event("submit", { bubbles: true })),
    );
    await expect(page.getByText("The name field is required.")).toBeVisible();
  });

  test("Full Name input has required attribute", async ({ page }) => {
    await expect(page.getByLabel("Full Name")).toHaveAttribute("required");
  });
});

// ---------------------------------------------------------------------------
// Change Password page (/account/password)
// ---------------------------------------------------------------------------

test.describe("Change Password page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.goto("/account/password");
  });

  test("renders all three password fields and submit button", async ({
    page,
  }) => {
    await expect(page.getByLabel("Current Password")).toBeVisible();
    await expect(
      page.getByLabel("New Password", { exact: true }),
    ).toBeVisible();
    await expect(page.getByLabel("Confirm New Password")).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Update Password" }),
    ).toBeEnabled();
  });

  test("shows field-level error on wrong current password", async ({
    page,
  }) => {
    await page.route("**/api/v1/user/password", (route) =>
      route.fulfill({
        status: 422,
        json: {
          errors: {
            current_password: ["The current password is incorrect."],
          },
        },
      }),
    );

    await page.getByLabel("Current Password").fill("wrongpassword");
    await page.getByLabel("New Password", { exact: true }).fill("newpassword1");
    await page.getByLabel("Confirm New Password").fill("newpassword1");
    await page.getByRole("button", { name: "Update Password" }).click();

    await expect(
      page.getByText("The current password is incorrect."),
    ).toBeVisible();
  });

  test("shows success message and clears form after update", async ({
    page,
  }) => {
    await page.route("**/api/v1/user/password", (route) =>
      route.fulfill({ status: 200, json: {} }),
    );

    await page.getByLabel("Current Password").fill("currentpass");
    await page.getByLabel("New Password", { exact: true }).fill("newpassword1");
    await page.getByLabel("Confirm New Password").fill("newpassword1");
    await page.getByRole("button", { name: "Update Password" }).click();

    await expect(page.getByText("Password updated successfully")).toBeVisible();
    await expect(page.getByLabel("Current Password")).toHaveValue("");
  });
});

// ---------------------------------------------------------------------------
// Addresses page (/account/addresses)
// ---------------------------------------------------------------------------

test.describe("Addresses page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/user/addresses", (route) =>
      route.fulfill({ json: mockAddresses }),
    );
    await page.goto("/account/addresses");
  });

  test("lists saved addresses with label and street", async ({ page }) => {
    await expect(page.getByText("Home")).toBeVisible();
    await expect(page.getByText("123 Rizal St")).toBeVisible();
    await expect(page.getByText("Office")).toBeVisible();
    await expect(page.getByText("456 Ayala Ave")).toBeVisible();
  });

  test("default address has a Default badge", async ({ page }) => {
    await expect(page.getByText("Default").first()).toBeVisible();
  });

  test("non-default address shows Set default button", async ({ page }) => {
    await expect(
      page.getByRole("button", { name: "Set default" }),
    ).toBeVisible();
  });

  test("opens Add-New modal with heading New Address", async ({ page }) => {
    await page.getByRole("button", { name: "Add New" }).click();
    await expect(
      page.getByRole("heading", { name: "New Address" }),
    ).toBeVisible();
    await expect(page.getByLabel(/Address Line 1/)).toBeVisible();
    await expect(page.getByLabel(/City/)).toBeVisible();
  });

  test("adds a new address via the modal form", async ({ page }) => {
    const newAddr = {
      id: 3,
      label: "Condo",
      line1: "789 BGC Blvd",
      line2: null,
      barangay: "BGC",
      city: "Taguig",
      province: "Metro Manila",
      postal_code: "1635",
      is_default: false,
    };

    let postDone = false;
    await page.route("**/api/v1/user/addresses", async (route) => {
      const method = route.request().method();
      if (method === "POST") {
        postDone = true;
        await route.fulfill({ json: newAddr });
      } else if (postDone) {
        await route.fulfill({ json: [...mockAddresses, newAddr] });
      } else {
        route.fallback();
      }
    });

    await page.getByRole("button", { name: "Add New" }).click();
    await page.getByLabel(/Address Line 1/).fill("789 BGC Blvd");
    await page.getByLabel(/City/).fill("Taguig");
    await page.getByLabel(/Province/).fill("Metro Manila");
    await page.getByLabel(/Postal Code/).fill("1635");
    await page.getByRole("button", { name: "Save Address" }).click();

    await expect(page.getByText("789 BGC Blvd")).toBeVisible();
  });

  test("opens edit modal pre-filled with existing address data", async ({
    page,
  }) => {
    // Pencil button is the first button in the first address row
    await page.locator("li").first().getByRole("button").nth(0).click();
    await expect(
      page.getByRole("heading", { name: "Edit Address" }),
    ).toBeVisible();
    await expect(page.getByLabel(/Address Line 1/)).toHaveValue("123 Rizal St");
  });

  test("removes an address after confirm dialog", async ({ page }) => {
    await page.route("**/api/v1/user/addresses/2", (route) => {
      if (route.request().method() === "DELETE") {
        route.fulfill({ status: 204, body: "" });
      }
    });

    page.on("dialog", (dialog) => dialog.accept());
    // Trash is the LAST button in the second address row
    await page.locator("li").nth(1).getByRole("button").last().click();

    await expect(page.getByText("Office")).not.toBeVisible();
  });

  test("dismissing the confirm dialog keeps the address in the list", async ({
    page,
  }) => {
    page.on("dialog", (dialog) => dialog.dismiss());
    await page.locator("li").nth(1).getByRole("button").last().click();
    await expect(page.getByText("Office")).toBeVisible();
  });

  test("sets an address as default and shows updated Default badge", async ({
    page,
  }) => {
    const updated = [
      { ...mockAddresses[0], is_default: false },
      { ...mockAddresses[1], is_default: true },
    ];

    await page.route("**/api/v1/user/addresses/2/default", (route) =>
      route.fulfill({ json: updated[1] }),
    );
    // Refetch returns the updated list
    let fetches = 0;
    await page.route("**/api/v1/user/addresses", (route) => {
      fetches++;
      route.fulfill({ json: fetches === 1 ? mockAddresses : updated });
    });

    await page.getByRole("button", { name: "Set default" }).click();
    await expect(page.locator("li").nth(1).getByText("Default")).toBeVisible();
  });

  test("shows empty state when no addresses are saved", async ({ page }) => {
    await page.route("**/api/v1/user/addresses", (route) =>
      route.fulfill({ json: [] }),
    );
    await page.goto("/account/addresses");
    await expect(page.getByText("No saved addresses")).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// Payment Methods page (/account/payment-methods)
// ---------------------------------------------------------------------------

test.describe("Payment Methods page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/user/payment-methods", (route) =>
      route.fulfill({ json: mockPaymentMethods }),
    );
    await page.goto("/account/payment-methods");
  });

  test("lists saved cards with masked digits", async ({ page }) => {
    await expect(page.getByText(/4242/)).toBeVisible();
    await expect(page.getByText(/5555/)).toBeVisible();
  });

  test("default card shows a Default badge", async ({ page }) => {
    await expect(page.getByText("Default").first()).toBeVisible();
  });

  test("non-default card shows Set default button", async ({ page }) => {
    await expect(
      page.getByRole("button", { name: "Set default" }),
    ).toBeVisible();
  });

  test("removes a card after confirm dialog", async ({ page }) => {
    await page.route("**/api/v1/user/payment-methods/2", (route) => {
      if (route.request().method() === "DELETE") {
        route.fulfill({ status: 204, body: "" });
      }
    });

    page.on("dialog", (dialog) => dialog.accept());
    await page.locator("li").nth(1).getByRole("button").last().click();

    await expect(page.getByText(/5555/)).not.toBeVisible();
  });

  test("card stays in list when dialog is dismissed", async ({ page }) => {
    page.on("dialog", (dialog) => dialog.dismiss());
    await page.locator("li").nth(1).getByRole("button").last().click();
    await expect(page.getByText(/5555/)).toBeVisible();
  });

  test("shows empty state when no payment methods are saved", async ({
    page,
  }) => {
    await page.route("**/api/v1/user/payment-methods", (route) =>
      route.fulfill({ json: [] }),
    );
    await page.goto("/account/payment-methods");
    await expect(page.getByText(/No saved payment method/i)).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// Settings page (/account/settings)
// ---------------------------------------------------------------------------

test.describe("Settings page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.goto("/account/settings");
  });

  test("shows Notifications and Security sections", async ({ page }) => {
    await expect(page.getByText("Notifications")).toBeVisible();
    await expect(page.getByText("Security")).toBeVisible();
  });

  test("order_updates is checked and promotions is unchecked on load", async ({
    page,
  }) => {
    const checkboxes = page.locator('input[type="checkbox"]');
    await expect(checkboxes.nth(0)).toBeChecked();
    await expect(checkboxes.nth(1)).not.toBeChecked();
  });

  test("saves preferences and shows success message", async ({ page }) => {
    let captured = null;
    await page.route("**/api/v1/user/settings", (route) => {
      captured = route.request().postDataJSON();
      route.fulfill({ json: mockUser });
    });

    await page.locator('input[type="checkbox"]').nth(1).check();
    await page.getByRole("button", { name: "Save Preferences" }).click();

    await expect(page.getByText("✓ Preferences saved")).toBeVisible();
    expect(captured?.notification_preferences?.promotions).toBe(true);
  });

  test("Change Password link points to /account/password", async ({ page }) => {
    const link = page.getByRole("link", { name: "Change Password" });
    await expect(link).toBeVisible();
    await expect(link).toHaveAttribute("href", "/account/password");
  });

  test("Delete My Account button is visible in danger zone", async ({
    page,
  }) => {
    await expect(
      page.getByRole("button", { name: "Delete My Account" }),
    ).toBeVisible();
  });

  test("delete modal: confirm button disabled until correct email typed", async ({
    page,
  }) => {
    await page.getByRole("button", { name: "Delete My Account" }).click();

    await expect(
      page.getByRole("heading", { name: "Delete your account?" }),
    ).toBeVisible();

    const confirmBtn = page.getByRole("button", { name: "Permanently Delete" });
    await expect(confirmBtn).toBeDisabled();

    await page.getByPlaceholder("juan@example.com").fill("wrong@example.com");
    await expect(confirmBtn).toBeDisabled();

    await page.getByPlaceholder("juan@example.com").fill("juan@example.com");
    await expect(confirmBtn).toBeEnabled();
  });

  test("Cancel button inside delete modal closes it", async ({ page }) => {
    await page.getByRole("button", { name: "Delete My Account" }).click();
    await expect(
      page.getByRole("heading", { name: "Delete your account?" }),
    ).toBeVisible();

    await page.getByRole("button", { name: "Cancel" }).click();
    await expect(
      page.getByRole("heading", { name: "Delete your account?" }),
    ).not.toBeVisible();
  });

  test("deletes account, clears token and navigates to /", async ({ page }) => {
    await page.evaluate(() => localStorage.setItem("api_token", "tok_test"));

    await page.route("**/api/v1/user", (route) => {
      if (route.request().method() === "DELETE") {
        route.fulfill({ status: 204, body: "" });
      } else {
        route.fallback();
      }
    });

    await page.getByRole("button", { name: "Delete My Account" }).click();
    await page.getByPlaceholder("juan@example.com").fill("juan@example.com");
    await page.getByRole("button", { name: "Permanently Delete" }).click();

    await expect(page).toHaveURL("/");
    const token = await page.evaluate(() => localStorage.getItem("api_token"));
    expect(token).toBeNull();
  });
});

// ---------------------------------------------------------------------------
// Orders list page (/account/orders)
// ---------------------------------------------------------------------------

test.describe("Orders list page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: mockOrders }),
    );
    await page.goto("/account/orders");
  });

  test("shows order IDs and status badges", async ({ page }) => {
    await expect(page.getByText("Order #ORD-001")).toBeVisible();
    await expect(page.getByText("Order #ORD-002")).toBeVisible();
    await expect(page.locator("li").first()).toContainText("delivered");
    await expect(page.locator("li").nth(1)).toContainText("pending");
  });

  test("clicking an order navigates to its detail page", async ({ page }) => {
    await page.route("**/api/v1/orders/ORD-001", (route) =>
      route.fulfill({
        json: { ...mockOrderPending, id: "ORD-001", status: "delivered" },
      }),
    );
    await page.getByRole("link", { name: "View →" }).first().click();
    await expect(page).toHaveURL("/account/orders/ORD-001");
  });

  test("shows empty state when no orders", async ({ page }) => {
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: { data: [] } }),
    );
    await page.goto("/account/orders");
    await expect(page.getByText("No orders yet")).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// Order Detail page (/account/orders/:id)
// ---------------------------------------------------------------------------

test.describe("Order Detail page", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/orders/ORD-002", (route) =>
      route.fulfill({ json: mockOrderPending }),
    );
    await page.goto("/account/orders/ORD-002");
  });

  test("shows order ID in the heading", async ({ page }) => {
    await expect(page.getByRole("heading", { name: /ORD-002/ })).toBeVisible();
  });

  test("shows line items and formatted total", async ({ page }) => {
    await expect(page.getByText(/Sinigang/)).toBeVisible();
    await expect(page.getByText("₱180.00").first()).toBeVisible();
  });

  test("shows status badge", async ({ page }) => {
    await expect(page.getByText("pending").first()).toBeVisible();
  });

  test("shows full status timeline for a non-cancelled order", async ({
    page,
  }) => {
    for (const label of [
      "Order Placed",
      "Confirmed",
      "Preparing",
      "Ready for Pickup",
      "Delivered",
    ]) {
      await expect(page.getByText(label)).toBeVisible();
    }
  });

  test("Cancel Order button is visible for pending orders", async ({
    page,
  }) => {
    await expect(
      page.getByRole("button", { name: "Cancel Order" }),
    ).toBeVisible();
  });

  test("cancelling shows cancelled banner and removes the cancel button", async ({
    page,
  }) => {
    await page.route("**/api/v1/orders/ORD-002/cancel", (route) =>
      route.fulfill({ json: { ...mockOrderPending, status: "cancelled" } }),
    );

    page.on("dialog", (dialog) => dialog.accept());
    await page.getByRole("button", { name: "Cancel Order" }).click();

    await expect(page.getByText("This order was cancelled.")).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Cancel Order" }),
    ).not.toBeVisible();
  });

  test("dismissing the cancel confirm keeps the timeline visible", async ({
    page,
  }) => {
    page.on("dialog", (dialog) => dialog.dismiss());
    await page.getByRole("button", { name: "Cancel Order" }).click();
    await expect(page.getByText("Order Placed")).toBeVisible();
  });

  test("cancelled order shows red banner instead of timeline", async ({
    page,
  }) => {
    await page.route("**/api/v1/orders/ORD-003", (route) =>
      route.fulfill({
        json: { ...mockOrderPending, id: "ORD-003", status: "cancelled" },
      }),
    );
    await page.goto("/account/orders/ORD-003");

    await expect(page.getByText("This order was cancelled.")).toBeVisible();
    await expect(page.getByText("Order Placed")).not.toBeVisible();
  });

  test("Cancel Order button is NOT visible for delivered orders", async ({
    page,
  }) => {
    await page.route("**/api/v1/orders/ORD-001", (route) =>
      route.fulfill({
        json: { ...mockOrderPending, id: "ORD-001", status: "delivered" },
      }),
    );
    await page.goto("/account/orders/ORD-001");
    await expect(
      page.getByRole("button", { name: "Cancel Order" }),
    ).not.toBeVisible();
  });

  test("Reorder button is present", async ({ page }) => {
    await expect(page.getByRole("button", { name: "Reorder" })).toBeVisible();
  });

  test("Reorder button posts to cart and navigates to /cart", async ({
    page,
  }) => {
    await page.route("**/api/v1/cart/lines", (route) =>
      route.fulfill({ json: {} }),
    );
    await page.route("**/api/v1/cart", (route) =>
      route.fulfill({ json: { lines: [], total: { formatted: "₱0.00" } } }),
    );

    await page.getByRole("button", { name: "Reorder" }).click();
    await expect(page).toHaveURL("/cart");
  });

  test("← My Orders breadcrumb navigates back to orders list", async ({
    page,
  }) => {
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: mockOrders }),
    );
    await page.getByRole("link", { name: "← My Orders" }).click();
    await expect(page).toHaveURL("/account/orders");
  });
});

// ---------------------------------------------------------------------------
// Cross-cutting: dashboard → sub-page navigation
// ---------------------------------------------------------------------------

test.describe("Dashboard navigation flows", () => {
  test.beforeEach(async ({ page }) => {
    await withAuth(page);
    await page.route("**/api/v1/orders*", (route) =>
      route.fulfill({ json: mockOrders }),
    );
    await page.goto("/account");
  });

  test("My Profile quick-link goes to /account/profile", async ({ page }) => {
    await page.getByRole("link", { name: /My Profile/ }).click();
    await expect(page).toHaveURL("/account/profile");
  });

  test("Settings quick-link goes to /account/settings", async ({ page }) => {
    await page.getByRole("link", { name: /^Settings$/ }).click();
    await expect(page).toHaveURL("/account/settings");
  });

  test("Payment Methods quick-link goes to /account/payment-methods", async ({
    page,
  }) => {
    await page.route("**/api/v1/user/payment-methods", (route) =>
      route.fulfill({ json: mockPaymentMethods }),
    );
    await page
      .getByRole("link", { name: /Payment Methods/ })
      .first()
      .click();
    await expect(page).toHaveURL("/account/payment-methods");
  });

  test("Addresses quick-link goes to /account/addresses", async ({ page }) => {
    await page.route("**/api/v1/user/addresses", (route) =>
      route.fulfill({ json: mockAddresses }),
    );
    await page.getByRole("link", { name: /^Addresses$/ }).click();
    await expect(page).toHaveURL("/account/addresses");
  });
});
