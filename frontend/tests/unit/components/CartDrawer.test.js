import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import CartDrawer from "@/components/CartDrawer.vue";
import { useCartStore } from "@/stores/cart";

vi.mock("@heroicons/vue/24/outline", () => ({
  XMarkIcon: { template: "<svg />" },
  TrashIcon: { template: "<svg />" },
}));

vi.mock("@/api/cart", () => ({
  cartApi: {
    get: vi.fn(),
    addItem: vi.fn(),
    updateItem: vi.fn(),
    removeItem: vi.fn(),
    clear: vi.fn(),
    shippingOptions: vi.fn(),
    setShippingOption: vi.fn(),
    setAddress: vi.fn(),
  },
}));

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: "/", component: { template: "<div />" } },
    { path: "/stores", component: { template: "<div />" } },
    { path: "/checkout", component: { template: "<div />" } },
  ],
});

function mountDrawer(props = {}) {
  setActivePinia(createPinia());
  return mount(CartDrawer, {
    props: { open: true, ...props },
    global: { plugins: [router] },
  });
}

describe("CartDrawer", () => {
  it("shows empty state when cart has no lines", () => {
    const wrapper = mountDrawer();
    expect(wrapper.text()).toContain("Your cart is empty");
  });

  it("renders cart lines when cart has items", () => {
    const wrapper = mountDrawer();
    const cart = useCartStore();
    cart.cart = {
      lines: [
        {
          id: "line_1",
          quantity: 1,
          purchasable: { name: "Lechon Kawali", thumbnail: null },
          sub_total: { formatted: "₱250.00" },
        },
      ],
      total: { formatted: "₱250.00" },
      meta: {},
    };

    const wrapper2 = mount(CartDrawer, {
      props: { open: true },
      global: { plugins: [router] },
    });

    expect(wrapper2.text()).toContain("Lechon Kawali");
    expect(wrapper2.text()).toContain("₱250.00");
  });

  it("emits close when backdrop is clicked", async () => {
    const wrapper = mountDrawer();
    // First div is the backdrop
    const backdrop = wrapper.find("div.fixed.inset-0");
    await backdrop.trigger("click");
    expect(wrapper.emitted("close")).toBeTruthy();
  });

  it("emits close when X button is clicked", async () => {
    const wrapper = mountDrawer();
    const closeBtn = wrapper.find("button");
    await closeBtn.trigger("click");
    expect(wrapper.emitted("close")).toBeTruthy();
  });

  it("shows Proceed to Checkout button when cart has items", () => {
    setActivePinia(createPinia());
    const cartStore = useCartStore();
    cartStore.cart = {
      lines: [
        {
          id: "l1",
          quantity: 1,
          purchasable: { name: "Test" },
          sub_total: { formatted: "₱50" },
        },
      ],
      total: { formatted: "₱50.00" },
      meta: {},
    };

    const wrapper = mount(CartDrawer, {
      props: { open: true },
      global: { plugins: [router] },
    });

    expect(wrapper.text()).toContain("Proceed to Checkout");
  });
});
