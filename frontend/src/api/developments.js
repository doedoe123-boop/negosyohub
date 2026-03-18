import client from "./client";

export const developmentsApi = {
  /**
   * @param {{ search?: string, city?: string, type?: string, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/v1/developments", { params });
  },

  show(slug) {
    return client.get(`/api/v1/developments/${slug}`);
  },
};
