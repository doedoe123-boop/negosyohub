import client from "./client";

export const marketInsightsApi = {
  show() {
    return client.get("/api/v1/market-insights");
  },
};
