import client from "./client";

export const storesApi = {
  /**
   * @param {{ sector?: string, city?: string, search?: string, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/stores", { params });
  },

  show(slug) {
    return client.get(`/api/stores/${slug}`);
  },

  products(slug, params = {}) {
    return client.get(`/api/stores/${slug}/products`, { params });
  },
};
