import client from "./client";

export const movingBookingsApi = {
  /**
   * List the authenticated customer's moving bookings.
   */
  list() {
    return client.get("/api/v1/moving-bookings");
  },

  /**
   * Get a single moving booking by ID.
   *
   * @param {number} id
   */
  show(id) {
    return client.get(`/api/v1/moving-bookings/${id}`);
  },

  /**
   * Create a new moving booking.
   *
   * @param {{ store_id: number, rental_agreement_id?: number, pickup_address: string, delivery_address: string, pickup_city: string, delivery_city: string, scheduled_at: string, contact_name: string, contact_phone: string, notes?: string, add_on_ids?: number[] }} payload
   */
  create(payload) {
    return client.post("/api/v1/moving-bookings", payload);
  },

  /**
   * Update the status of a moving booking (store owner/admin only).
   *
   * @param {number} id
   * @param {string} status
   */
  updateStatus(id, status) {
    return client.patch(`/api/v1/moving-bookings/${id}/status`, { status });
  },

  /**
   * Cancel a booking (customer, pending bookings only).
   *
   * @param {number} id
   */
  cancel(id) {
    return client.patch(`/api/v1/moving-bookings/${id}/cancel`);
  },

  /**
   * Submit a review for a completed booking.
   *
   * @param {number} bookingId
   * @param {{ rating: number, comment?: string }} payload
   */
  submitReview(bookingId, payload) {
    return client.post(`/api/v1/moving-bookings/${bookingId}/review`, payload);
  },
};
