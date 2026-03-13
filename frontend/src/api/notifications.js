import client from "./client";

export const notificationsApi = {
  /** Fetch the user's unread notifications. */
  list() {
    return client.get("/api/v1/user/notifications");
  },

  /** Mark a single notification as read. */
  markRead(id) {
    return client.patch(`/api/v1/user/notifications/${id}/read`);
  },

  /** Mark all notifications as read. */
  markAllRead() {
    return client.post("/api/v1/user/notifications/read-all");
  },
};
