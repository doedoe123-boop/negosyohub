import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import Navbar from "@/components/Navbar.vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";

// Stub heroicons so we don't need to resolve the actual SVG imports
vi.mock("@heroicons/vue/24/outline", () => ({
  ShoppingCartIcon: { template: "<svg />" },
  Bars3Icon: { template: "<svg />" },
  XMarkIcon: { template: "<svg />" },
  UserCircleIcon: { template: "<svg />" },
  MagnifyingGlassIcon: { template: "<svg />" },
  MoonIcon: { template: "<svg />" },
  SunIcon: { template: "<svg />" },
}));

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: "/", component: { template: "<div />" } },
    { path: "/stores", component: { template: "<div />" } },
    { path: "/login", component: { template: "<div />" } },
    { path: "/register", component: { template: "<div />" } },
    { path: "/account/orders", component: { template: "<div />" } },
  ],
});

function mountNavbar() {
  setActivePinia(createPinia());
  return mount(Navbar, {
    global: { plugins: [router] },
  });
}

describe("Navbar", () => {
  it("renders the brand name", () => {
    const wrapper = mountNavbar();
    expect(wrapper.text()).toContain("NegosyoHub");
  });

  it("shows Sign In and Register when guest", () => {
    const wrapper = mountNavbar();
    expect(wrapper.text()).toContain("Sign in");
    expect(wrapper.text()).toContain("Register");
  });

  it("shows Account and Sign out when authenticated", () => {
    setActivePinia(createPinia());
    const auth = useAuthStore();
    auth.user = { id: 1, name: "Juan", role: "customer" };

    const wrapper = mount(Navbar, { global: { plugins: [router] } });

    expect(wrapper.text()).toContain("Account");
    expect(wrapper.text()).toContain("Sign out");
  });

  it("shows cart badge when items in cart", async () => {
    setActivePinia(createPinia());
    const cartStore = useCartStore();
    cartStore.cart = {
      lines: [{ id: "l1", quantity: 3 }],
      total: { formatted: "₱300.00" },
      meta: {},
    };

    const wrapper = mount(Navbar, { global: { plugins: [router] } });
    expect(wrapper.text()).toContain("1");
  });

  it("calls cart.toggleDrawer when cart button is clicked", async () => {
    setActivePinia(createPinia());
    const cartStore = useCartStore();
    const toggleSpy = vi.spyOn(cartStore, "toggleDrawer");

    const wrapper = mount(Navbar, { global: { plugins: [router] } });
    await wrapper.find('[aria-label="Shopping cart"]').trigger("click");

    expect(toggleSpy).toHaveBeenCalled();
  });
});
