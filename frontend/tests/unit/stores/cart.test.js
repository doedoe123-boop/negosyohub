import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useCartStore } from "@/stores/cart";
import * as cartModule from "@/api/cart";

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

const mockCart = (overrides = {}) => ({
  lines: [
    {
      id: "line_1",
      quantity: 2,
      purchasable: { name: "Adobo", thumbnail: null },
      sub_total: { formatted: "₱200.00" },
    },
  ],
  total: { formatted: "₱200.00" },
  meta: { store_id: 5 },
  ...overrides,
});

describe("Cart Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it("starts empty", () => {
    const cart = useCartStore();
    expect(cart.cart).toBeNull();
    expect(cart.lineCount).toBe(0);
    expect(cart.totalQuantity).toBe(0);
  });

  it("fetch — populates cart", async () => {
    cartModule.cartApi.get.mockResolvedValue({ data: mockCart() });

    const cart = useCartStore();
    await cart.fetch();

    expect(cart.lineCount).toBe(1);
    expect(cart.totalQuantity).toBe(2);
    expect(cart.total).toBe("₱200.00");
    expect(cart.storeId).toBe(5);
  });

  it("addItem — updates cart and opens drawer", async () => {
    const updated = mockCart();
    cartModule.cartApi.addItem.mockResolvedValue({ data: updated });

    const cart = useCartStore();
    await cart.addItem("product-variant", 42, 1, {});

    expect(cartModule.cartApi.addItem).toHaveBeenCalledWith(
      "product-variant",
      42,
      1,
      {},
    );
    expect(cart.isOpen).toBe(true);
  });

  it("removeItem — calls API and updates cart", async () => {
    const withoutLine = mockCart({ lines: [] });
    cartModule.cartApi.removeItem.mockResolvedValue({ data: withoutLine });

    const cart = useCartStore();
    cart.cart = mockCart();

    await cart.removeItem("line_1");

    expect(cart.lineCount).toBe(0);
  });

  it("clear — nulls the cart", async () => {
    cartModule.cartApi.clear.mockResolvedValue({});

    const cart = useCartStore();
    cart.cart = mockCart();

    await cart.clear();

    expect(cart.cart).toBeNull();
  });

  it("drawer — openDrawer / closeDrawer / toggleDrawer", () => {
    const cart = useCartStore();
    expect(cart.isOpen).toBe(false);

    cart.openDrawer();
    expect(cart.isOpen).toBe(true);

    cart.closeDrawer();
    expect(cart.isOpen).toBe(false);

    cart.toggleDrawer();
    expect(cart.isOpen).toBe(true);
  });
});
