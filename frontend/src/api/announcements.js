import client from "./client";

export const announcementsApi = {
  list(params = {}) {
    return client.get("/api/v1/announcements", { params });
  },
};
