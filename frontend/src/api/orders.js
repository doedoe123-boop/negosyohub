import client from "./client";

export const ordersApi = {
  list(params = {}) {
    return client.get("/api/orders", { params });
  },

  show(id) {
    return client.get(`/api/orders/${id}`);
  },

  place(payload) {
    return client.post("/api/orders", payload);
  },
};
