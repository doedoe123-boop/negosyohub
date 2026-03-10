import client from "./client";

export const featuredListingsApi = {
  list(params = {}) {
    return client.get("/api/v1/featured-listings", { params });
  },
};
