import client from "./client";

export const paypalApi = {
  /**
   * Create a PayPal order from the current cart.
   * Returns { paypal_order_id, approve_url }.
   */
  createOrder() {
    return client.post("/api/v1/paypal/create-order");
  },

  /**
   * Capture a PayPal order after customer approval.
   * Returns the placed order data.
   */
  captureOrder(paypalOrderId) {
    return client.post("/api/v1/paypal/capture-order", {
      paypal_order_id: paypalOrderId,
    });
  },
};
