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

  /**
   * List published properties for a real-estate store.
   *
   * @param {string} slug
   * @param {{ search?: string, type?: string, listing_type?: string, min_price?: number, max_price?: number, bedrooms?: number, per_page?: number, page?: number }} params
   */
  properties(slug, params = {}) {
    return client.get(`/api/v1/stores/${slug}/properties`, { params });
  },
};
