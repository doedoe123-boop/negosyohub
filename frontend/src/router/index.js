import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const routes = [
  // ─── Public storefront ──────────────────────────────────────────────────
  {
    path: "/",
    component: () => import("@/layouts/DefaultLayout.vue"),
    children: [
      {
        path: "",
        name: "home",
        component: () => import("@/pages/Home.vue"),
      },
      {
        path: "stores",
        name: "stores.index",
        component: () => import("@/pages/Stores.vue"),
      },
      {
        path: "stores/:slug",
        name: "stores.show",
        component: () => import("@/pages/store/StoreDetail.vue"),
      },
      {
        path: "products/:id",
        name: "products.show",
        component: () => import("@/pages/product/ProductDetail.vue"),
      },
      {
        path: "cart",
        name: "cart.index",
        component: () => import("@/pages/cart/CartPage.vue"),
        meta: { requiresAuth: true },
      },
      // ─── Real Estate ──────────────────────────────────────────────────
      {
        path: "properties",
        name: "properties.index",
        component: () => import("@/pages/realty/Properties.vue"),
      },
      {
        path: "properties/compare",
        name: "properties.compare",
        component: () => import("@/pages/realty/CompareProperties.vue"),
      },
      {
        path: "properties/:slug",
        name: "properties.show",
        component: () => import("@/pages/realty/PropertyDetail.vue"),
      },
      {
        path: "developments",
        name: "developments.index",
        component: () => import("@/pages/realty/Developments.vue"),
      },
      {
        path: "developments/:slug",
        name: "developments.show",
        component: () => import("@/pages/realty/DevelopmentDetail.vue"),
      },
      {
        path: "agent/:slug",
        name: "agent.show",
        component: () => import("@/pages/realty/AgentDetail.vue"),
      },
      // ─── Lipat Bahay / Moving Service ────────────────────────────────
      {
        path: "movers",
        name: "movers.index",
        component: () => import("@/pages/movers/MoversPage.vue"),
      },
      {
        path: "movers/:slug",
        name: "movers.show",
        component: () => import("@/pages/movers/MoverDetail.vue"),
      },
    ],
  },

  // ─── Checkout (own layout — no footer distraction) ───────────────────
  {
    path: "/checkout",
    component: () => import("@/layouts/CheckoutLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "checkout.index",
        component: () => import("@/pages/checkout/CheckoutPage.vue"),
      },
      {
        path: "success",
        name: "checkout.success",
        component: () => import("@/pages/checkout/CheckoutSuccess.vue"),
      },
    ],
  },

  // ─── Auth ─────────────────────────────────────────────────────────────
  {
    path: "/",
    component: () => import("@/layouts/AuthLayout.vue"),
    meta: { guestOnly: true },
    children: [
      {
        path: "login",
        name: "auth.login",
        component: () => import("@/pages/auth/LoginPage.vue"),
      },
      {
        path: "register",
        name: "auth.register",
        component: () => import("@/pages/auth/RegisterPage.vue"),
      },
      {
        path: "forgot-password",
        name: "auth.forgot-password",
        component: () => import("@/pages/auth/ForgotPassword.vue"),
      },
      {
        path: "reset-password",
        name: "auth.reset-password",
        component: () => import("@/pages/auth/ResetPassword.vue"),
      },
    ],
  },

  // ─── Customer account (requires auth) ────────────────────────────────
  {
    path: "/account",
    component: () => import("@/layouts/DefaultLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        component: () => import("@/layouts/AccountLayout.vue"),
        children: [
          {
            path: "",
            name: "account.dashboard",
            component: () => import("@/pages/account/AccountDashboard.vue"),
          },
          {
            path: "orders",
            name: "account.orders",
            component: () => import("@/pages/account/OrdersPage.vue"),
          },
          {
            path: "orders/:id",
            name: "account.orders.show",
            component: () => import("@/pages/account/OrderDetail.vue"),
          },
          {
            path: "inquiries",
            name: "account.inquiries",
            component: () => import("@/pages/account/InquiriesPage.vue"),
          },
          {
            path: "saved-searches",
            name: "account.saved-searches",
            component: () => import("@/pages/account/SavedSearches.vue"),
          },
          {
            path: "agreements",
            name: "account.agreements",
            component: () => import("@/pages/account/RentalAgreementsPage.vue"),
          },
          {
            path: "addresses",
            name: "account.addresses",
            component: () => import("@/pages/account/AddressesPage.vue"),
          },
          {
            path: "payment-methods",
            name: "account.payment-methods",
            component: () => import("@/pages/account/PaymentMethodsPage.vue"),
          },
          {
            path: "profile",
            name: "account.profile",
            component: () => import("@/pages/account/ProfilePage.vue"),
          },
          {
            path: "password",
            name: "account.password",
            component: () => import("@/pages/account/ChangePasswordPage.vue"),
          },
          {
            path: "settings",
            name: "account.settings",
            component: () => import("@/pages/account/SettingsPage.vue"),
          },
          {
            path: "help",
            name: "account.help",
            component: () => import("@/pages/account/HelpPage.vue"),
          },
          // ─── Moving bookings ────────────────────────────────────────
          {
            path: "moving",
            name: "account.moving",
            component: () => import("@/pages/moving/MovingBookingsPage.vue"),
          },
          {
            path: "moving/:id",
            name: "account.moving.show",
            component: () => import("@/pages/moving/MovingBookingDetail.vue"),
          },
        ],
      },
    ],
  },

  // ─── 404 ─────────────────────────────────────────────────────────────
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/pages/NotFound.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition ?? { top: 0 };
  },
});

// Navigation guards
router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.initialized) {
    const hasToken = !!sessionStorage.getItem("api_token");
    if (hasToken) {
      await auth.fetchUser();
    } else {
      auth.initialized = true;
    }
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: "auth.login", query: { redirect: to.fullPath } };
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: "home" };
  }
});

export default router;
