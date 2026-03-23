import { beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createMemoryHistory, createRouter } from "vue-router";
import { createPinia, setActivePinia } from "pinia";
import CheckoutPage from "@/pages/checkout/CheckoutPage.vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";

vi.mock("@/api/orders", () => ({
  ordersApi: {
    place: vi.fn(),
  },
}));

vi.mock("@/api/cart", () => ({
  cartApi: {
    setAddress: vi.fn(),
    shippingOptions: vi.fn(),
    setShippingOption: vi.fn(),
  },
}));

vi.mock("@/api/paypal", () => ({
  paypalApi: {
    createOrder: vi.fn(),
  },
}));

vi.mock("@/api/addresses", () => ({
  addressesApi: {
    list: vi.fn(),
  },
}));

vi.mock("@/i18n", () => ({
  useAppI18n: () => ({
    t: (key) => key,
    setLocale: vi.fn().mockResolvedValue(undefined),
  }),
}));

vi.mock("@/components/CouponInput.vue", () => ({
  default: {
    template: "<div data-testid='coupon-input'>Coupon Input</div>",
  },
}));

const mockCart = {
  lines: [
    {
      id: "line-1",
      quantity: 2,
      purchasable: { name: "Wireless Earbuds" },
      sub_total: { formatted: "₱1,200.00", value: 120000 },
    },
  ],
  total: { formatted: "₱1,350.00", value: 135000 },
  original_total: { formatted: "₱1,350.00", value: 135000 },
  discount_total: { formatted: "₱0.00", value: 0 },
  shipping_total: { formatted: "₱150.00", value: 15000 },
  tax_total: { formatted: "₱0.00", value: 0 },
  meta: { store_id: 8 },
};

let pinia;

async function mountCheckout() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/cart", component: { template: "<div>cart</div>" } },
      { path: "/checkout", component: CheckoutPage },
      { path: "/checkout/success", component: { template: "<div>success</div>" } },
    ],
  });

  await router.push("/checkout");
  await router.isReady();

  const wrapper = mount(CheckoutPage, {
    global: {
      plugins: [pinia, router],
    },
  });

  await flushPromises();
  await flushPromises();

  return { wrapper, router };
}

describe("Checkout page", () => {
  beforeEach(async () => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    sessionStorage.clear();

    const { addressesApi } = await import("@/api/addresses");
    addressesApi.list.mockResolvedValue({
      data: [
        {
          id: 1,
          line1: "123 Sampaguita Street",
          city: "Pasig",
          province: "Metro Manila",
          postal_code: "1600",
          is_default: true,
        },
      ],
    });
  });

  it("redirects back to cart when the cart is empty", async () => {
    const cart = useCartStore();
    cart.fetch = vi.fn().mockImplementation(async () => {
      cart.cart = {
        lines: [],
        total: { formatted: "₱0.00", value: 0 },
        meta: {},
      };
    });

    const auth = useAuthStore();
    auth.user = null;

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: "/cart", component: { template: "<div>cart</div>" } },
        { path: "/checkout", component: CheckoutPage },
      ],
    });

    await router.push("/checkout");
    await router.isReady();

    mount(CheckoutPage, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(router.currentRoute.value.path).toBe("/cart");
  });

  it("prefills the address form from the user profile and default address", async () => {
    const cart = useCartStore();
    cart.fetch = vi.fn().mockImplementation(async () => {
      cart.cart = structuredClone(mockCart);
    });

    const auth = useAuthStore();
    auth.user = {
      id: 1,
      name: "Juan Dela Cruz",
      phone: "09171234567",
      email: "juan@example.com",
    };

    const { wrapper } = await mountCheckout();

    expect(wrapper.find("#addr-first-name").element.value).toBe("Juan");
    expect(wrapper.find("#addr-last-name").element.value).toBe("Dela Cruz");
    expect(wrapper.find("#addr-line-one").element.value).toBe("123 Sampaguita Street");
    expect(wrapper.find("#addr-city").element.value).toBe("Pasig");
    expect(wrapper.find("#addr-state").element.value).toBe("Metro Manila");
    expect(wrapper.find("#addr-postcode").element.value).toBe("1600");
    expect(wrapper.find("#addr-phone").element.value).toBe("09171234567");
    expect(wrapper.find("#addr-email").element.value).toBe("juan@example.com");
  });

  it("advances from address to shipping and then payment", async () => {
    const { cartApi } = await import("@/api/cart");

    cartApi.setAddress.mockResolvedValue({});
    cartApi.shippingOptions.mockResolvedValue({
      data: [
        {
          id: "ship-standard",
          name: "Standard Delivery",
          description: "2-3 business days",
          price: { formatted: "₱150.00" },
        },
      ],
    });
    cartApi.setShippingOption.mockResolvedValue({});

    const cart = useCartStore();
    cart.fetch = vi.fn().mockImplementation(async () => {
      cart.cart = structuredClone(mockCart);
    });

    const auth = useAuthStore();
    auth.user = { id: 1, name: "Maria Santos", email: "maria@example.com" };

    const { wrapper } = await mountCheckout();

    await wrapper.find("form").trigger("submit");
    await flushPromises();

    expect(wrapper.text()).toContain("Shipping Method");

    const shippingRadio = wrapper.find('input[type="radio"][value="ship-standard"]');
    await shippingRadio.setValue();
    await wrapper.findAll("button").find((button) => button.text() === "Continue to Payment").trigger("click");
    await flushPromises();

    expect(cartApi.setAddress).toHaveBeenCalledOnce();
    expect(cartApi.setShippingOption).toHaveBeenCalledWith("ship-standard");
    expect(wrapper.text()).toContain("Cash on Delivery");
  });

  it("places a COD order directly and routes to the success page", async () => {
    const { cartApi } = await import("@/api/cart");
    const { ordersApi } = await import("@/api/orders");

    cartApi.setAddress.mockResolvedValue({});
    cartApi.shippingOptions.mockResolvedValue({
      data: [{ id: "ship-standard", name: "Standard Delivery", description: "", price: { formatted: "₱150.00" } }],
    });
    cartApi.setShippingOption.mockResolvedValue({});
    ordersApi.place.mockResolvedValue({
      data: { order_id: 99 },
    });

    const cart = useCartStore();
    cart.fetch = vi.fn().mockImplementation(async () => {
      cart.cart = structuredClone(mockCart);
    });
    cart.reset = vi.fn();

    const auth = useAuthStore();
    auth.user = { id: 1, name: "Maria Santos", email: "maria@example.com" };

    const { wrapper, router } = await mountCheckout();

    await wrapper.find("form").trigger("submit");
    await flushPromises();
    await wrapper.find('input[type="radio"][value="ship-standard"]').setValue();
    await wrapper.findAll("button").find((button) => button.text() === "Continue to Payment").trigger("click");
    await flushPromises();
    await wrapper.find('input[type="radio"][value="cash_on_delivery"]').setValue();
    await wrapper.findAll("button").find((button) => button.text() === "Place COD Order").trigger("click");
    await flushPromises();

    expect(ordersApi.place).toHaveBeenCalledWith({
      store_id: 8,
      payment_method: "cash_on_delivery",
    });
    expect(cart.reset).toHaveBeenCalledOnce();
    expect(router.currentRoute.value.path).toBe("/checkout/success");
    expect(router.currentRoute.value.query.order).toBe("99");
  });
});
