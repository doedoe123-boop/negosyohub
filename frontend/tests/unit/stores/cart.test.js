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
    applyCoupon: vi.fn(),
    removeCoupon: vi.fn(),
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

  it("applyCoupon — stores discount totals and applied coupon", async () => {
    cartModule.cartApi.applyCoupon.mockResolvedValue({
      data: mockCart({
        total: { formatted: "₱180.00", value: 18000 },
        original_total: { formatted: "₱200.00", value: 20000 },
        discount_total: { formatted: "₱20.00", value: 2000 },
        applied_coupon: { code: "SAVE20", description: "Save 20 pesos" },
      }),
    });

    const cart = useCartStore();
    await cart.applyCoupon("SAVE20");

    expect(cart.appliedCoupon).toEqual({
      code: "SAVE20",
      description: "Save 20 pesos",
    });
    expect(cart.discountTotal).toBe("₱20.00");
    expect(cart.originalTotal).toBe("₱200.00");
  });

  it("removeCoupon — clears the applied coupon state", async () => {
    cartModule.cartApi.removeCoupon.mockResolvedValue({
      data: mockCart({
        applied_coupon: null,
        discount_total: { formatted: "₱0.00", value: 0 },
      }),
    });

    const cart = useCartStore();
    cart.cart = mockCart({
      applied_coupon: { code: "SAVE20" },
      discount_total: { formatted: "₱20.00", value: 2000 },
    });

    await cart.removeCoupon();

    expect(cart.appliedCoupon).toBeNull();
    expect(cart.discountTotal).toBe("₱0.00");
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
