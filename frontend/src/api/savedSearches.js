import client from "./client";

export const savedSearchesApi = {
  list() {
    return client.get("/api/v1/user/saved-searches");
  },

  /**
   * @param {{ name: string, criteria: object, notify_frequency?: string, is_active?: boolean }} payload
   */
  create(payload) {
    return client.post("/api/v1/user/saved-searches", payload);
  },

  delete(id) {
    return client.delete(`/api/v1/user/saved-searches/${id}`);
  },

  toggle(id) {
    return client.patch(`/api/v1/user/saved-searches/${id}/toggle`);
  },
};
