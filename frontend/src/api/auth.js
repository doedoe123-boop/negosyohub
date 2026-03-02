import client, { initCsrf } from "./client";

export const authApi = {
  async register(payload) {
    await initCsrf();
    return client.post("/api/register/customer", payload);
  },

  async login(credentials) {
    await initCsrf();
    return client.post("/api/login", credentials);
  },

  async logout() {
    return client.post("/api/logout");
  },

  async me() {
    return client.get("/api/user");
  },
};
