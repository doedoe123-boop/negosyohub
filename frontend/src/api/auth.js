import client, { initCsrf } from "./client";

export const authApi = {
  async register(payload) {
    await initCsrf();
    return client.post("/api/v1/register", payload);
  },

  async login(credentials) {
    await initCsrf();
    return client.post("/api/v1/login", credentials);
  },

  async logout() {
    return client.post("/api/v1/logout");
  },

  async me() {
    return client.get("/api/v1/user");
  },
};
