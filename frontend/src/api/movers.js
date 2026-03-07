import client from "./client";

export const moversApi = {
  /**
   * Browse Lipat Bahay moving companies.
   *
   * @param {{ city?: string, province?: string, per_page?: number, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/v1/movers", { params });
  },

  /**
   * Get a single moving company by slug, including active add-ons.
   *
   * @param {string} slug
   */
  show(slug) {
    return client.get(`/api/v1/movers/${slug}`);
  },
};
