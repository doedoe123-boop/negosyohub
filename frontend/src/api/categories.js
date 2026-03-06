import client from "./client";

export const categoriesApi = {
  /** GET /api/v1/categories — returns marketplace category collections */
  list() {
    return client.get("/api/v1/categories");
  },
};
