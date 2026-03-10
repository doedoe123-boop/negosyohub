import client from "./client";

export const couponsApi = {
  validate(code) {
    return client.post("/api/v1/coupons/validate", { code });
  },
};
