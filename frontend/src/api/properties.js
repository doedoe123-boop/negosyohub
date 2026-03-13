import client from "./client";

export const propertiesApi = {
  /**
   * @param {{ search?: string, type?: string, listing_type?: string, min_price?: number, max_price?: number, bedrooms?: number, city?: string, featured?: boolean, per_page?: number, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/v1/properties", { params });
  },

  show(slug) {
    return client.get(`/api/v1/properties/${slug}`);
  },

  featured(limit = 4) {
    return client.get("/api/v1/properties", {
      params: { per_page: limit, featured: true },
    });
  },

  /**
   * Submit a customer inquiry for a property.
   *
   * @param {string} slug
   * @param {{ name: string, email: string, phone?: string, message?: string, source?: string }} payload
   */
  submitInquiry(slug, payload) {
    return client.post(`/api/v1/properties/${slug}/inquiries`, payload);
  },

  /**
   * Submit a quick inquiry using the authenticated user's details.
   *
   * @param {string} slug
   * @param {{ message?: string }} payload
   */
  quickInquiry(slug, payload = {}) {
    return client.post(`/api/v1/properties/${slug}/quick-inquiry`, payload);
  },

  /**
   * List upcoming open house events for a property.
   *
   * @param {string} slug
   */
  openHouses(slug) {
    return client.get(`/api/v1/properties/${slug}/open-houses`);
  },
};
