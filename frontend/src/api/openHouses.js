import client from "./client";

export const openHousesApi = {
  /**
   * Register an RSVP for an open house event.
   *
   * @param {number} openHouseId
   * @param {{ name: string, email: string, phone?: string, notes?: string }} payload
   */
  rsvp(openHouseId, payload) {
    return client.post(`/api/v1/open-houses/${openHouseId}/rsvp`, payload);
  },
};
