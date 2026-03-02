import client from "./client";

export const productsApi = {
  show(id) {
    return client.get(`/api/v1/products/${id}`);
  },

  /**
   * @param {{ search?: string, category?: string, min_price?: number, max_price?: number, page?: number }} params
   */
  list(params = {}) {
    return client.get("/api/v1/products", { params });
  },
};
