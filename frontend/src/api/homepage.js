import client from "./client";

export const homepageApi = {
  /**
   * Fetch aggregate homepage stats (stores, products, properties, avg rating).
   * Cached server-side for 5 minutes.
   */
  stats() {
    return client.get("/api/v1/homepage-stats");
  },
};
