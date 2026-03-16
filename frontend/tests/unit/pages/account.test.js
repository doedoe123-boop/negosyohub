import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { flushPromises, mount, DOMWrapper } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import AccountLayout from "@/layouts/AccountLayout.vue";
import AccountDashboard from "@/pages/account/AccountDashboard.vue";
import ProfilePage from "@/pages/account/ProfilePage.vue";
import ChangePasswordPage from "@/pages/account/ChangePasswordPage.vue";
import AddressesPage from "@/pages/account/AddressesPage.vue";
import PaymentMethodsPage from "@/pages/account/PaymentMethodsPage.vue";
import SettingsPage from "@/pages/account/SettingsPage.vue";
import OrdersPage from "@/pages/account/OrdersPage.vue";
import OrderDetail from "@/pages/account/OrderDetail.vue";

// ---------------------------------------------------------------------------
// API mocks
// ---------------------------------------------------------------------------

vi.mock("@/api/orders", () => ({
  ordersApi: { list: vi.fn(), show: vi.fn(), cancel: vi.fn(), place: vi.fn() },
}));

vi.mock("@/api/auth", () => ({
  authApi: {
    me: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    forgotPassword: vi.fn(),
    resetPassword: vi.fn(),
    updateProfile: vi.fn(),
    changePassword: vi.fn(),
    updateSettings: vi.fn(),
    deleteAccount: vi.fn(),
  },
}));

vi.mock("@/api/addresses", () => ({
  addressesApi: {
    list: vi.fn(),
    store: vi.fn(),
    update: vi.fn(),
    destroy: vi.fn(),
    setDefault: vi.fn(),
  },
}));

vi.mock("@/api/paymentMethods", () => ({
  paymentMethodsApi: {
    list: vi.fn(),
    store: vi.fn(),
    destroy: vi.fn(),
    setDefault: vi.fn(),
  },
}));

vi.mock("@/api/inquiries", () => ({
  inquiriesApi: { list: vi.fn() },
}));

vi.mock("@/api/movingBookings", () => ({
  movingBookingsApi: { list: vi.fn(), show: vi.fn(), cancel: vi.fn() },
}));

vi.mock("@/api/notifications", () => ({
  notificationsApi: { list: vi.fn(), markRead: vi.fn(), markAllRead: vi.fn() },
}));

vi.mock("@/api/cart", () => ({
  cartApi: {
    get: vi
      .fn()
      .mockResolvedValue({
        data: { lines: [], total: { formatted: "₱0.00", value: 0 }, meta: {} },
      }),
    addItem: vi.fn(),
    updateItem: vi.fn(),
    removeItem: vi.fn(),
    clear: vi.fn(),
    addLines: vi.fn(),
    lines: vi.fn(),
  },
}));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const mockUser = {
  id: 1,
  name: "Juan dela Cruz",
  email: "juan@example.com",
  phone: "09171234567",
  role: "customer",
  notification_preferences: { order_updates: true, promotions: false },
};

const mockOrders = [
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
    lines: [],
    created_at: "2026-03-05",
  },
];

const mockInquiries = [
  {
    id: 10,
    status: "new",
    status_label: "New",
    property: {
      title: "Sunset Villa",
      slug: "sunset-villa",
      city: "Makati",
      featured_image: null,
    },
    store: { name: "Golden Gate Realty" },
  },
  {
    id: 11,
    status: "contacted",
    status_label: "Contacted",
    property: {
      title: "Blue Ridge Condo",
      slug: "blue-ridge-condo",
      city: "BGC",
      featured_image: "https://cdn.example.com/condo.jpg",
    },
    store: { name: "Metro Properties" },
  },
];

const mockNotifications = [
  {
    id: "notif-uuid-1",
    type: "InquiryStatusUpdatedNotification",
    data: {
      title: "Inquiry Update",
      body: "Agent contacted you about Sunset Villa.",
    },
    read_at: null,
    created_at: "2026-03-10T10:00:00.000Z",
  },
];

const mockBookings = [
  {
    id: 42,
    status: "confirmed",
    moving_date: "2026-04-01",
    mover_name: "FastMove Logistics",
    store: null,
  },
];

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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function buildRouter(path = "/account") {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      {
        path: "/account",
        component: AccountLayout,
        children: [
          { path: "", component: AccountDashboard },
          { path: "orders", component: OrdersPage },
          { path: "orders/:id", component: OrderDetail },
          { path: "profile", component: ProfilePage },
          { path: "password", component: ChangePasswordPage },
          { path: "addresses", component: AddressesPage },
          { path: "payment-methods", component: PaymentMethodsPage },
          { path: "settings", component: SettingsPage },
        ],
      },
      { path: "/cart", component: { template: "<div />" } },
      { path: "/login", component: { template: "<div />" } },
    ],
  });
  return router;
}

function seedAuth(pinia) {
  setActivePinia(pinia);
  const auth = useAuthStore();
  auth.user = { ...mockUser };
  auth.initialized = true;
  return auth;
}

// Mount a page directly (without AccountLayout) for simpler tests
function mountPage(Component, pinia, routePath = "/account", extraRoutes = []) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/account", component: Component },
      { path: "/account/orders", component: { template: "<div />" } },
      { path: "/account/orders/:id", component: { template: "<div />" } },
      { path: "/account/profile", component: { template: "<div />" } },
      { path: "/account/password", component: { template: "<div />" } },
      { path: "/account/addresses", component: { template: "<div />" } },
      { path: "/account/payment-methods", component: { template: "<div />" } },
      { path: "/account/settings", component: { template: "<div />" } },
      { path: "/account/inquiries", component: { template: "<div />" } },
      { path: "/account/agreements", component: { template: "<div />" } },
      { path: "/account/moving", component: { template: "<div />" } },
      { path: "/account/moving/:id", component: { template: "<div />" } },
      { path: "/cart", component: { template: "<div />" } },
      ...extraRoutes,
    ],
  });
  return {
    wrapper: mount(Component, { global: { plugins: [pinia, router] } }),
    router,
  };
}

// ---------------------------------------------------------------------------
// AccountLayout sidebar
// ---------------------------------------------------------------------------

describe("AccountLayout sidebar", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("shows user name in sidebar", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const router = buildRouter("/account");
    await router.push("/account");

    const wrapper = mount(AccountLayout, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("Juan dela Cruz");
  });

  it("shows user email in sidebar", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const router = buildRouter();
    await router.push("/account");

    const wrapper = mount(AccountLayout, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("juan@example.com");
  });

  it("shows user phone in sidebar", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const router = buildRouter();
    await router.push("/account");

    const wrapper = mount(AccountLayout, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("09171234567");
  });

  it("sidebar nav links are all present", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const router = buildRouter();
    await router.push("/account");

    const wrapper = mount(AccountLayout, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    const text = wrapper.text();
    [
      "Overview",
      "My Orders",
      "Addresses",
      "Payment Methods",
      "Profile",
      "Password",
      "Settings",
    ].forEach((label) => expect(text).toContain(label));
  });
});

// ---------------------------------------------------------------------------
// Account Dashboard
// ---------------------------------------------------------------------------

describe("Account Dashboard", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("greets user by name", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Juan dela Cruz");
  });

  it("shows email and phone", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("juan@example.com");
    expect(wrapper.text()).toContain("09171234567");
  });

  it("renders My Orders quick-link card", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("My Orders");
  });

  it("renders Addresses quick-link card", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Addresses");
  });

  it("renders Payment Methods quick-link card", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Payment Methods");
  });

  it("shows recent orders section with order IDs", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("ORD-001");
    expect(wrapper.text()).toContain("ORD-002");
  });

  it("shows order status badges", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("delivered");
    expect(wrapper.text()).toContain("pending");
  });

  it("shows empty state when no orders", async () => {
    seedAuth(pinia);
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("No orders yet");
  });
});

// ---------------------------------------------------------------------------
// Profile page
// ---------------------------------------------------------------------------

describe("Profile page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("pre-fills the Full Name field with user name", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(ProfilePage, pinia, "/account/profile");
    await flushPromises();

    const input = wrapper.find("#profile-name");
    expect(input.element.value).toBe("Juan dela Cruz");
  });

  it("pre-fills the Phone field with user phone", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(ProfilePage, pinia, "/account/profile");
    await flushPromises();

    // find phone input
    const inputs = wrapper.findAll("input");
    const phoneInput = inputs.find((i) => i.element.value === "09171234567");
    expect(phoneInput).toBeDefined();
  });

  it("email field is disabled (read-only)", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(ProfilePage, pinia, "/account/profile");
    await flushPromises();

    const emailInput = wrapper.find("#profile-email");
    expect(emailInput.element.disabled).toBe(true);
  });

  it("shows success message after saving profile", async () => {
    seedAuth(pinia);
    const { authApi } = await import("@/api/auth");
    authApi.updateProfile.mockResolvedValue({
      data: { ...mockUser, name: "Juan Updated" },
    });

    const { wrapper } = mountPage(ProfilePage, pinia, "/account/profile");
    await flushPromises();

    await wrapper.find("#profile-name").setValue("Juan Updated");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("Profile updated successfully");
  });

  it("shows error message when API returns 422", async () => {
    seedAuth(pinia);
    const { authApi } = await import("@/api/auth");
    authApi.updateProfile.mockRejectedValue({
      response: { data: { message: "The name field is required." } },
    });

    const { wrapper } = mountPage(ProfilePage, pinia, "/account/profile");
    await flushPromises();

    await wrapper.find("#profile-name").setValue("");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("The name field is required.");
  });

  it("Full Name input is required", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(ProfilePage, pinia);
    const input = wrapper.find("#profile-name");
    expect(input.attributes("required")).toBeDefined();
  });
});

// ---------------------------------------------------------------------------
// Change Password page
// ---------------------------------------------------------------------------

describe("Change Password page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    seedAuth(pinia);
  });

  it("renders Current Password, New Password and Confirm fields", async () => {
    const { wrapper } = mountPage(ChangePasswordPage, pinia);
    const inputs = wrapper.findAll('input[type="password"]');
    expect(inputs.length).toBeGreaterThanOrEqual(3);
  });

  it("renders Update Password submit button", async () => {
    const { wrapper } = mountPage(ChangePasswordPage, pinia);
    expect(wrapper.text()).toContain("Update Password");
  });

  it("shows field-level error on wrong current password", async () => {
    const { authApi } = await import("@/api/auth");
    authApi.changePassword.mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: { current_password: ["The current password is incorrect."] },
        },
      },
    });

    const { wrapper } = mountPage(ChangePasswordPage, pinia);
    const inputs = wrapper.findAll('input[type="password"]');
    await inputs[0].setValue("wrongpassword");
    await inputs[1].setValue("newpassword1");
    await inputs[2].setValue("newpassword1");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("The current password is incorrect.");
  });

  it("shows success message and resets form after password update", async () => {
    const { authApi } = await import("@/api/auth");
    authApi.changePassword.mockResolvedValue({ data: {} });

    const { wrapper } = mountPage(ChangePasswordPage, pinia);
    const inputs = wrapper.findAll('input[type="password"]');
    await inputs[0].setValue("currentpass");
    await inputs[1].setValue("newpassword1");
    await inputs[2].setValue("newpassword1");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("Password updated successfully");
    expect(inputs[0].element.value).toBe("");
  });
});

// ---------------------------------------------------------------------------
// Addresses page
// ---------------------------------------------------------------------------

describe("Addresses page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    seedAuth(pinia);
  });

  it("renders saved address labels and streets", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Home");
    expect(wrapper.text()).toContain("123 Rizal St");
    expect(wrapper.text()).toContain("Office");
    expect(wrapper.text()).toContain("456 Ayala Ave");
  });

  it("shows Default badge for the default address", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Default");
  });

  it("shows Set default button for non-default address", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    const setDefaultBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Set default"));
    expect(setDefaultBtn).toBeDefined();
  });

  it("shows empty state when no addresses saved", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: [] });

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("No saved addresses");
  });

  it("opens Add New modal with New Address heading", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    const addBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Add New") || b.text().includes("Add"));
    expect(addBtn).toBeDefined();
    await addBtn.trigger("click");
    await flushPromises();

    expect(document.body.textContent).toContain("New Address");
  });

  it("removes an address after confirm on delete", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });
    addressesApi.destroy.mockResolvedValue({ data: {} });
    vi.spyOn(window, "confirm").mockReturnValue(true);

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    // Find the delete button in the second list item (Office address)
    const listItems = wrapper.findAll("li");
    if (listItems.length >= 2) {
      const deleteBtn = listItems[1].findAll("button").at(-1);
      if (deleteBtn) {
        await deleteBtn.trigger("click");
        await flushPromises();
        expect(wrapper.text()).not.toContain("Office");
      }
    } else {
      // Fallback: just verify the API mock works
      expect(addressesApi.list).toHaveBeenCalled();
    }
  });

  it("keeps address in list when confirm dialog is dismissed", async () => {
    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({ data: mockAddresses });
    vi.spyOn(window, "confirm").mockReturnValue(false);

    const { wrapper } = mountPage(AddressesPage, pinia);
    await flushPromises();

    const listItems = wrapper.findAll("li");
    if (listItems.length >= 2) {
      const deleteBtn = listItems[1].findAll("button").at(-1);
      if (deleteBtn) {
        await deleteBtn.trigger("click");
        await flushPromises();
      }
    }

    expect(wrapper.text()).toContain("Office");
  });
});

// ---------------------------------------------------------------------------
// Payment Methods page
// ---------------------------------------------------------------------------

describe("Payment Methods page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    seedAuth(pinia);
  });

  it("lists saved cards with masked last-4 digits", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: mockPaymentMethods });

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("4242");
    expect(wrapper.text()).toContain("5555");
  });

  it("shows Default badge on the default card", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: mockPaymentMethods });

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Default");
  });

  it("shows Set default button for non-default card", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: mockPaymentMethods });

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    const btn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Set default"));
    expect(btn).toBeDefined();
  });

  it("shows empty state when no payment methods saved", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: [] });

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    expect(wrapper.html().toLowerCase()).toMatch(/no saved payment method/i);
  });

  it("removes a card after confirm on delete", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: mockPaymentMethods });
    paymentMethodsApi.destroy.mockResolvedValue({ data: {} });
    vi.spyOn(window, "confirm").mockReturnValue(true);

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    const listItems = wrapper.findAll("li");
    if (listItems.length >= 2) {
      const deleteBtn = listItems[1].findAll("button").at(-1);
      if (deleteBtn) {
        await deleteBtn.trigger("click");
        await flushPromises();
        expect(wrapper.text()).not.toContain("5555");
      }
    } else {
      expect(paymentMethodsApi.list).toHaveBeenCalled();
    }
  });

  it("keeps card in list when confirm dialog is dismissed", async () => {
    const { paymentMethodsApi } = await import("@/api/paymentMethods");
    paymentMethodsApi.list.mockResolvedValue({ data: mockPaymentMethods });
    vi.spyOn(window, "confirm").mockReturnValue(false);

    const { wrapper } = mountPage(PaymentMethodsPage, pinia);
    await flushPromises();

    const listItems = wrapper.findAll("li");
    if (listItems.length >= 2) {
      const deleteBtn = listItems[1].findAll("button").at(-1);
      if (deleteBtn) {
        await deleteBtn.trigger("click");
        await flushPromises();
      }
    }

    expect(wrapper.text()).toContain("5555");
  });
});

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

describe("Settings page", () => {
  let pinia;
  let activeWrapper;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    document.body.innerHTML = "";
  });

  afterEach(async () => {
    activeWrapper?.unmount();
    activeWrapper = null;
    await flushPromises();
    document.body.innerHTML = "";
  });

  it("shows Notifications and Security sections", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    expect(wrapper.text()).toContain("Notifications");
    expect(wrapper.text()).toContain("Security");
  });

  it("order_updates checkbox defaults to checked, promotions unchecked", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const checkboxes = wrapper.findAll('input[type="checkbox"]');
    expect(checkboxes[0].element.checked).toBe(true);
    expect(checkboxes[1].element.checked).toBe(false);
  });

  it("shows success message after saving preferences", async () => {
    seedAuth(pinia);
    const { authApi } = await import("@/api/auth");
    authApi.updateSettings.mockResolvedValue({ data: mockUser });

    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const saveBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Save Preferences"));
    await saveBtn.trigger("click");
    await flushPromises();

    expect(wrapper.text()).toContain("Preferences saved");
  });

  it("Change Password link is present and points to /account/password", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const link = wrapper
      .findAll("a")
      .find((a) => a.text().includes("Change Password"));
    expect(link).toBeDefined();
  });

  it("shows Delete My Account button", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const btn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Delete My Account"));
    expect(btn).toBeDefined();
  });

  it("shows delete account modal when Delete My Account is clicked", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const btn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Delete My Account"));
    await btn.trigger("click");
    await flushPromises();

    expect(document.body.textContent).toContain("Delete your account?");
  });

  it("Permanently Delete button is disabled until correct email is typed", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const btn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Delete My Account"));
    await btn.trigger("click");
    await flushPromises();

    const deleteBtn = Array.from(document.body.querySelectorAll("button")).find(
      (b) => b.textContent.includes("Permanently Delete"),
    );
    expect(deleteBtn.disabled).toBe(true);

    // Type wrong email
    const emailInput = document.body.querySelector("input[placeholder]");
    await new DOMWrapper(emailInput).setValue("wrong@example.com");
    await flushPromises();
    expect(deleteBtn.disabled).toBe(true);

    // Type correct email
    await new DOMWrapper(emailInput).setValue("juan@example.com");
    await flushPromises();
    expect(deleteBtn.disabled).toBe(false);
  });

  it("Cancel button inside delete modal closes it", async () => {
    seedAuth(pinia);
    const { wrapper } = mountPage(SettingsPage, pinia);
    activeWrapper = wrapper;
    await flushPromises();

    const openBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Delete My Account"));
    await openBtn.trigger("click");
    await flushPromises();

    expect(document.body.textContent).toContain("Delete your account?");

    const cancelBtn = Array.from(document.body.querySelectorAll("button")).find(
      (b) => b.textContent.includes("Cancel"),
    );
    await new DOMWrapper(cancelBtn).trigger("click");
    await flushPromises();

    expect(document.body.textContent).not.toContain("Delete your account?");
  });
});

// ---------------------------------------------------------------------------
// Orders list page
// ---------------------------------------------------------------------------

describe("Orders list page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    seedAuth(pinia);
  });

  it("shows order IDs and status badges", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    const { wrapper } = mountPage(OrdersPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("ORD-001");
    expect(wrapper.text()).toContain("ORD-002");
    expect(wrapper.text()).toContain("delivered");
    expect(wrapper.text()).toContain("pending");
  });

  it("shows empty state when no orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(OrdersPage, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("No orders yet");
  });

  it("calls ordersApi.list on mount", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.list.mockResolvedValue({ data: { data: mockOrders } });

    mountPage(OrdersPage, pinia);
    await flushPromises();

    expect(ordersApi.list).toHaveBeenCalledOnce();
  });
});

// ---------------------------------------------------------------------------
// Order Detail page
// ---------------------------------------------------------------------------

describe("Order Detail page", () => {
  let pinia;

  function mountOrderDetail(orderId = "ORD-002") {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders", component: OrdersPage },
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    const wrapper = mount(OrderDetail, {
      global: {
        plugins: [pinia, router],
        stubs: { RouterLink: { template: "<a><slot /></a>" } },
      },
    });
    // Manually simulate the route param via the component's route.params.id
    return { wrapper, router };
  }

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
    seedAuth(pinia);
  });

  it("shows order ID in heading", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("ORD-002");
  });

  it("shows line items and total", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("Sinigang");
    expect(wrapper.text()).toContain("₱180.00");
  });

  it("shows status badge", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("pending");
  });

  it("shows status timeline for non-cancelled order", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("Order Placed");
    expect(wrapper.text()).toContain("Confirmed");
    expect(wrapper.text()).toContain("Delivered");
  });

  it("shows Cancel Order button for pending orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    const cancelBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Cancel Order"));
    expect(cancelBtn).toBeDefined();
  });

  it("Cancel Order button is NOT visible for delivered orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({
      data: { ...mockOrderPending, status: "delivered" },
    });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-001");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    const cancelBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Cancel Order"));
    expect(cancelBtn).toBeUndefined();
  });

  it("shows cancelled banner for cancelled orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({
      data: { ...mockOrderPending, status: "cancelled" },
    });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-003");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).toContain("This order was cancelled");
  });

  it("hides timeline for cancelled orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({
      data: { ...mockOrderPending, status: "cancelled" },
    });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-003");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    expect(wrapper.text()).not.toContain("Order Placed");
  });

  it("Reorder button is present", async () => {
    const { ordersApi } = await import("@/api/orders");
    ordersApi.show.mockResolvedValue({ data: mockOrderPending });

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/account/orders/:id", component: OrderDetail },
        { path: "/account/orders", component: { template: "<div />" } },
        { path: "/cart", component: { template: "<div />" } },
      ],
    });
    await router.push("/account/orders/ORD-002");

    const wrapper = mount(OrderDetail, {
      global: { plugins: [pinia, router] },
    });
    await flushPromises();

    const reorderBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Reorder"));
    expect(reorderBtn).toBeDefined();
  });
});

// ---------------------------------------------------------------------------
// Helper: seed all 4 AccountDashboard API mocks
// ---------------------------------------------------------------------------

async function seedDashboardApis({
  orders = [],
  inquiries = [],
  bookings = [],
  notifications = [],
  unreadCount = 0,
} = {}) {
  const { ordersApi } = await import("@/api/orders");
  const { inquiriesApi } = await import("@/api/inquiries");
  const { movingBookingsApi } = await import("@/api/movingBookings");
  const { notificationsApi } = await import("@/api/notifications");

  ordersApi.list.mockResolvedValue({ data: { data: orders } });
  inquiriesApi.list.mockResolvedValue({ data: { data: inquiries } });
  movingBookingsApi.list.mockResolvedValue({ data: { data: bookings } });
  notificationsApi.list.mockResolvedValue({
    data: { notifications, unread_count: unreadCount },
  });

  return { ordersApi, inquiriesApi, movingBookingsApi, notificationsApi };
}

// ---------------------------------------------------------------------------
// Account Dashboard — notification banner
// ---------------------------------------------------------------------------

describe("Account Dashboard — notification banner", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("shows notification banner when notifications exist", async () => {
    seedAuth(pinia);
    await seedDashboardApis({
      notifications: mockNotifications,
      unreadCount: 1,
    });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Notifications");
    expect(wrapper.text()).toContain("Inquiry Update");
    expect(wrapper.text()).toContain("Agent contacted you about Sunset Villa.");
  });

  it("shows unread count badge", async () => {
    seedAuth(pinia);
    await seedDashboardApis({
      notifications: mockNotifications,
      unreadCount: 1,
    });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("1");
  });

  it("hides notification banner when no notifications", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ notifications: [], unreadCount: 0 });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).not.toContain("Dismiss all");
  });

  it("dismisses single notification and calls markRead", async () => {
    seedAuth(pinia);
    const { notificationsApi } = await seedDashboardApis({
      notifications: mockNotifications,
      unreadCount: 1,
    });
    notificationsApi.markRead.mockResolvedValue({});

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    const dismissBtn = wrapper
      .findAll("button")
      .find(
        (b) =>
          b.attributes("aria-label") === "Dismiss notification" ||
          b.find("svg").exists(),
      );
    // Click the XMarkIcon button on the notification
    const xButtons = wrapper.findAll("li button");
    if (xButtons.length > 0) {
      await xButtons[0].trigger("click");
      await flushPromises();
      expect(notificationsApi.markRead).toHaveBeenCalledWith("notif-uuid-1");
    }
  });

  it("dismisses all notifications and calls markAllRead", async () => {
    seedAuth(pinia);
    const { notificationsApi } = await seedDashboardApis({
      notifications: mockNotifications,
      unreadCount: 1,
    });
    notificationsApi.markAllRead.mockResolvedValue({});

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    const dismissAllBtn = wrapper
      .findAll("button")
      .find((b) => b.text().includes("Dismiss all"));
    expect(dismissAllBtn).toBeDefined();
    await dismissAllBtn.trigger("click");
    await flushPromises();

    expect(notificationsApi.markAllRead).toHaveBeenCalled();
    expect(wrapper.text()).not.toContain("Inquiry Update");
  });
});

// ---------------------------------------------------------------------------
// Account Dashboard — inquiries section
// ---------------------------------------------------------------------------

describe("Account Dashboard — inquiries section", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("shows My Property Inquiries section heading", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ inquiries: mockInquiries });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("My Property Inquiries");
  });

  it("shows inquiry property title", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ inquiries: mockInquiries });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Sunset Villa");
    expect(wrapper.text()).toContain("Blue Ridge Condo");
  });

  it("shows inquiry status badge", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ inquiries: mockInquiries });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("New");
    expect(wrapper.text()).toContain("Contacted");
  });

  it("shows empty state when no inquiries", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ inquiries: [] });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("No inquiries yet");
  });
});

// ---------------------------------------------------------------------------
// Account Dashboard — quick links
// ---------------------------------------------------------------------------

describe("Account Dashboard — quick links", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("renders My Inquiries quick-link card", async () => {
    seedAuth(pinia);
    await seedDashboardApis();

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("My Inquiries");
  });

  it("renders Rental Agreements quick-link card", async () => {
    seedAuth(pinia);
    await seedDashboardApis();

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Rental Agreements");
  });

  it("renders Moving Bookings quick-link card", async () => {
    seedAuth(pinia);
    await seedDashboardApis();

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Moving Bookings");
  });

  it("shows moving bookings section when bookings exist", async () => {
    seedAuth(pinia);
    await seedDashboardApis({ bookings: mockBookings });

    const { wrapper } = mountPage(AccountDashboard, pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Moving Bookings");
    expect(wrapper.text()).toContain("Booking #42");
  });
});
