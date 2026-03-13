import client from "./client";

export const supportApi = {
  list(params = {}) {
    return client.get("/api/v1/user/support-tickets", { params });
  },
  show(id) {
    return client.get(`/api/v1/user/support-tickets/${id}`);
  },
  create(data) {
    return client.post("/api/v1/user/support-tickets", data);
  },
};
