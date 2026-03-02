import client from "./client";

export const storesApi = {
  /**
   * @param {{ sector?: string, city?: string, search?: string, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/v1/stores", { params });
  },

  show(slug) {
    return client.get(`/api/v1/stores/${slug}`);
  },

  products(slug, params = {}) {
    return client.get(`/api/v1/stores/${slug}/products`, { params });
  },
};
