import client from "./client";

export const reviewsApi = {
  /**
   * List published reviews for a store.
   *
   * @param {string} slug
   * @param {{ page?: number }} params
   */
  listForStore(slug, params = {}) {
    return client.get(`/api/v1/stores/${slug}/reviews`, { params });
  },

  /**
   * Submit a review for a store. Requires authentication.
   *
   * @param {string} slug
   * @param {{ rating: number, title?: string, content: string }} payload
   */
  submitForStore(slug, payload) {
    return client.post(`/api/v1/stores/${slug}/reviews`, payload);
  },

  /**
   * List published reviews for a product.
   *
   * @param {number} productId
   * @param {{ page?: number }} params
   */
  listForProduct(productId, params = {}) {
    return client.get(`/api/v1/products/${productId}/reviews`, { params });
  },

  /**
   * Submit a review for a product. Requires authentication.
   *
   * @param {number} productId
   * @param {{ rating: number, title?: string, content: string }} payload
   */
  submitForProduct(productId, payload) {
    return client.post(`/api/v1/products/${productId}/reviews`, payload);
  },

  /**
   * List published reviews for a property.
   *
   * @param {string} slug
   * @param {{ page?: number }} params
   */
  listForProperty(slug, params = {}) {
    return client.get(`/api/v1/properties/${slug}/reviews`, { params });
  },

  /**
   * Submit a review for a property. Requires authentication.
   *
   * @param {string} slug
   * @param {{ rating: number, title?: string, content: string }} payload
   */
  submitForProperty(slug, payload) {
    return client.post(`/api/v1/properties/${slug}/reviews`, payload);
  },
};
