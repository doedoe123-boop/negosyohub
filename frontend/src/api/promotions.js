import client from "./client";

export const promotionsApi = {
  list(params = {}) {
    return client.get("/api/v1/promotions", { params });
  },
};
