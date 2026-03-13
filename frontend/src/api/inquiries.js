import client from "./client";

export const inquiriesApi = {
  /** List the current user's property inquiries (paginated). */
  list(params = {}) {
    return client.get("/api/v1/user/inquiries", { params });
  },
};
