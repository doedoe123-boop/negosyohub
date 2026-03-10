import client from "./client";

export const advertisementsApi = {
  list(params = {}) {
    return client.get("/api/v1/advertisements", { params });
  },
};
