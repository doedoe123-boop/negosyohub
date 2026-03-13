import client from "./client";

export const agreementsApi = {
  list() {
    return client.get("/api/v1/user/rental-agreements");
  },
  update(id, data) {
    return client.patch(`/api/v1/user/rental-agreements/${id}`, data);
  },
};
