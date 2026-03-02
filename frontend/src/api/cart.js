import client from "./client";

export const cartApi = {
  get() {
    return client.get("/api/cart");
  },

  addItem(purchasableType, purchasableId, quantity = 1, meta = {}) {
    return client.post("/api/cart/lines", {
      purchasable_type: purchasableType,
      purchasable_id: purchasableId,
      quantity,
      meta,
    });
  },

  updateItem(lineId, quantity) {
    return client.patch(`/api/cart/lines/${lineId}`, { quantity });
  },

  removeItem(lineId) {
    return client.delete(`/api/cart/lines/${lineId}`);
  },

  clear() {
    return client.delete("/api/cart");
  },

  shippingOptions() {
    return client.get("/api/cart/shipping-options");
  },

  setShippingOption(shippingRateId) {
    return client.post("/api/cart/shipping-option", {
      shipping_rate_id: shippingRateId,
    });
  },

  setAddress(payload) {
    return client.post("/api/cart/address", payload);
  },
};
