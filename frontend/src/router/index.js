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
      },
      // ─── Real Estate ──────────────────────────────────────────────────
      {
        path: "properties",
        name: "properties.index",
        component: () => import("@/pages/realty/Properties.vue"),
      },
      {
        path: "properties/:slug",
        name: "properties.show",
        component: () => import("@/pages/realty/PropertyDetail.vue"),
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
    ],
  },

  // ─── Customer account (requires auth) ────────────────────────────────
  {
    path: "/account",
    component: () => import("@/layouts/DefaultLayout.vue"),
    meta: { requiresAuth: true },
    children: [
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
        path: "profile",
        name: "account.profile",
        component: () => import("@/pages/account/ProfilePage.vue"),
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
    await auth.fetchUser();
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: "auth.login", query: { redirect: to.fullPath } };
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: "home" };
  }
});

export default router;
