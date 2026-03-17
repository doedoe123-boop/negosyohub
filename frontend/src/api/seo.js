import client from "./client";

export const seoApi = {
  /** Fetch global SEO & analytics settings. Cached by the SPA for the session. */
  global() {
    return client.get("/api/v1/seo/global");
  },
};
