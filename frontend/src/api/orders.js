import client from "./client";

export const ordersApi = {
  list(params = {}) {
    return client.get("/api/v1/orders", { params });
  },

  show(id) {
    return client.get(`/api/v1/orders/${id}`);
  },

  place(payload) {
    return client.post("/api/v1/orders", payload);
  },

  /**
   * Cancel an order by its ID.
   *
   * @param {number} id
   */
  cancel(id) {
    return client.patch(`/api/v1/orders/${id}/cancel`);
  },
};
