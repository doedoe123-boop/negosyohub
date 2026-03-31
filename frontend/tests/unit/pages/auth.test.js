import { describe, it, expect, vi, beforeEach } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import LoginPage from "@/pages/auth/LoginPage.vue";
import RegisterPage from "@/pages/auth/RegisterPage.vue";
import AuthLayout from "@/layouts/AuthLayout.vue";

// ---------------------------------------------------------------------------
// API mocks
// ---------------------------------------------------------------------------

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

// Required so the auth store's login() / register() don't blow up on CSRF init
vi.mock("@/api/client", () => ({
  default: { get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() },
  initCsrf: vi.fn().mockResolvedValue(undefined),
}));

vi.mock("@/api/homepage", () => ({
  homepageApi: {
    stats: vi.fn().mockResolvedValue({
      data: { stores: 24, properties: 9, products: 112 },
    }),
  },
}));

vi.mock("@/components/TurnstileWidget.vue", () => ({
  default: {
    name: "TurnstileWidget",
    template: '<div data-test="turnstile-widget"></div>',
  },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Minimal memory router that mirrors the real route guards. */
function createTestRouter(authStoreRef) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div>home</div>" } },
      {
        path: "/login",
        meta: { guestOnly: true },
        component: { template: "<div>login</div>" },
      },
      {
        path: "/register",
        meta: { guestOnly: true },
        component: { template: "<div>register</div>" },
      },
      {
        path: "/account",
        meta: { requiresAuth: true },
        component: { template: "<div>account</div>" },
      },
      {
        path: "/account/orders",
        meta: { requiresAuth: true },
        component: { template: "<div>orders</div>" },
      },
      {
        path: "/account/addresses",
        meta: { requiresAuth: true },
        component: { template: "<div>addresses</div>" },
      },
      {
        path: "/account/payment-methods",
        meta: { requiresAuth: true },
        component: { template: "<div>payment-methods</div>" },
      },
      {
        path: "/account/profile",
        meta: { requiresAuth: true },
        component: { template: "<div>profile</div>" },
      },
      {
        path: "/account/password",
        meta: { requiresAuth: true },
        component: { template: "<div>password</div>" },
      },
      {
        path: "/account/settings",
        meta: { requiresAuth: true },
        component: { template: "<div>settings</div>" },
      },
      {
        path: "/checkout",
        meta: { requiresAuth: true },
        component: { template: "<div>checkout</div>" },
      },
    ],
  });

  router.beforeEach((to) => {
    const auth = authStoreRef ?? useAuthStore();
    if (!auth.initialized) {
      auth.initialized = true;
    }
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
      return { path: "/login", query: { redirect: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.isLoggedIn) {
      return { path: "/" };
    }
  });

  return router;
}

function mountLogin(pinia) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/login", component: LoginPage },
      { path: "/register", component: { template: "<div />" } },
      { path: "/forgot-password", component: { template: "<div />" } },
    ],
  });
  return mount(LoginPage, { global: { plugins: [pinia, router] } });
}

function mountRegister(pinia) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/register", component: RegisterPage },
      { path: "/login", component: { template: "<div />" } },
    ],
  });
  return mount(RegisterPage, { global: { plugins: [pinia, router] } });
}

async function mountAuthLayout() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: "/login",
        component: AuthLayout,
        children: [{ path: "", component: LoginPage }],
      },
      { path: "/", component: { template: "<div />" } },
    ],
  });

  await router.push("/login");
  await router.isReady();

  return mount(AuthLayout, { global: { plugins: [router] } });
}

// ---------------------------------------------------------------------------
// Route guards
// ---------------------------------------------------------------------------

describe("Account route guard", () => {
  const protectedPaths = [
    "/account",
    "/account/orders",
    "/account/addresses",
    "/account/payment-methods",
    "/account/profile",
    "/account/password",
    "/account/settings",
  ];

  protectedPaths.forEach((path) => {
    it(`redirects ${path} to /login when unauthenticated`, async () => {
      const pinia = createPinia();
      setActivePinia(pinia);
      const auth = useAuthStore();
      auth.initialized = true; // guest — no user
      const router = createTestRouter(auth);

      await router.push(path);

      expect(router.currentRoute.value.path).toBe("/login");
    });
  });

  it("redirects authenticated user away from /login to /", async () => {
    const pinia = createPinia();
    setActivePinia(pinia);
    const auth = useAuthStore();
    auth.user = { id: 1, name: "Juan", role: "customer" };
    auth.initialized = true;
    const router = createTestRouter(auth);

    await router.push("/login");

    expect(router.currentRoute.value.path).toBe("/");
  });

  it("redirects unauthenticated user from /checkout to /login", async () => {
    const pinia = createPinia();
    setActivePinia(pinia);
    const auth = useAuthStore();
    auth.initialized = true;
    const router = createTestRouter(auth);

    await router.push("/checkout");

    expect(router.currentRoute.value.path).toBe("/login");
  });

  it("allows authenticated user to access /account", async () => {
    const pinia = createPinia();
    setActivePinia(pinia);
    const auth = useAuthStore();
    auth.user = { id: 1, name: "Juan", role: "customer" };
    auth.initialized = true;
    const router = createTestRouter(auth);

    await router.push("/account");

    expect(router.currentRoute.value.path).toBe("/account");
  });
});

// ---------------------------------------------------------------------------
// Login page
// ---------------------------------------------------------------------------

describe("Login page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  it("renders the Welcome back heading", () => {
    const wrapper = mountLogin(pinia);
    expect(wrapper.text()).toContain("Welcome back");
  });

  it("renders email and password fields", () => {
    const wrapper = mountLogin(pinia);
    expect(wrapper.find("#login-email").exists()).toBe(true);
    expect(wrapper.find("#login-password").exists()).toBe(true);
  });

  it("renders Sign In submit button", () => {
    const wrapper = mountLogin(pinia);
    const btn = wrapper.find("button[type=submit]");
    expect(btn.exists()).toBe(true);
    expect(btn.text()).toContain("Sign In");
  });

  it("shows error message when login fails", async () => {
    const { authApi } = await import("@/api/auth");
    authApi.login.mockRejectedValue({
      response: { data: { message: "Invalid credentials." } },
    });

    const auth = useAuthStore();
    auth.login = vi
      .fn()
      .mockRejectedValue({ response: { data: { message: "Invalid credentials." } } });

    const wrapper = mountLogin(pinia);
    await wrapper.find("#login-email").setValue("bad@example.com");
    await wrapper.find("#login-password").setValue("wrongpassword");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("Invalid credentials.");
  });

  it("calls auth.login with form values on submit", async () => {
    const auth = useAuthStore();
    auth.login = vi.fn().mockResolvedValue({ token: "tok", user: { id: 1, name: "Juan", role: "customer" } });

    const wrapper = mountLogin(pinia);
    await wrapper.find("#login-email").setValue("juan@example.com");
    await wrapper.find("#login-password").setValue("password");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(auth.login).toHaveBeenCalledWith({
      email: "juan@example.com",
      password: "password",
      turnstile_token: "",
    });
  });

  it("renders live marketplace stats and no longer mentions Stripe in the auth layout", async () => {
    const wrapper = await mountAuthLayout();
    await flushPromises();

    expect(wrapper.text()).toContain("24");
    expect(wrapper.text()).toContain("Stores");
    expect(wrapper.text()).toContain("9");
    expect(wrapper.text()).toContain("Properties");
    expect(wrapper.text()).toContain("112");
    expect(wrapper.text()).toContain("Products");
    expect(wrapper.text()).not.toContain("Stripe");
  });
});

// ---------------------------------------------------------------------------
// Register page
// ---------------------------------------------------------------------------

describe("Register page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  it("renders the Create your account heading", () => {
    const wrapper = mountRegister(pinia);
    expect(wrapper.text()).toContain("Create your account");
  });

  it("renders Full Name, Email and Password fields", () => {
    const wrapper = mountRegister(pinia);
    const text = wrapper.html();
    expect(text).toContain("Full Name");
    expect(text).toContain("Email");
    expect(text).toContain("Password");
  });

  it("renders Create Account submit button", () => {
    const wrapper = mountRegister(pinia);
    const btn = wrapper.find("button[type=submit]");
    expect(btn.exists()).toBe(true);
    expect(btn.text()).toContain("Create Account");
  });

  it("shows API validation error", async () => {
    const auth = useAuthStore();
    auth.register = vi.fn().mockRejectedValue({
      response: {
        status: 422,
        data: { errors: { email: ["The email has already been taken."] } },
      },
    });

    const wrapper = mountRegister(pinia);
    await wrapper.find('input[type="text"]').setValue("Maria Santos");
    await wrapper.find('input[type="email"]').setValue("taken@example.com");
    // find first password field
    const passwordInputs = wrapper.findAll('input[type="password"]');
    await passwordInputs[0].setValue("password123");
    await passwordInputs[1].setValue("password123");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("The email has already been taken.");
  });

  it("calls auth.register with form values on submit", async () => {
    const auth = useAuthStore();
    auth.register = vi.fn().mockResolvedValue({
      token: "tok",
      user: { id: 1, name: "Maria", role: "customer" },
    });

    const wrapper = mountRegister(pinia);
    await wrapper.find("#register-name").setValue("Maria Santos");
    await wrapper.find("#register-email").setValue("maria@example.com");
    const passwordInputs = wrapper.findAll('input[type="password"]');
    await passwordInputs[0].setValue("Password#123");
    await passwordInputs[1].setValue("Password#123");
    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(auth.register).toHaveBeenCalledWith({
      name: "Maria Santos",
      email: "maria@example.com",
      password: "Password#123",
      password_confirmation: "Password#123",
      turnstile_token: "",
    });
  });
});
