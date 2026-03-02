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
};
