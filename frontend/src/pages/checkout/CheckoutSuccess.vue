<script setup>
import { ref, onMounted } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { CheckCircleIcon } from "@heroicons/vue/24/solid";
import { ordersApi } from "@/api/orders";
import { useCartStore } from "@/stores/cart";
import { paypalApi } from "@/api/paypal";

const route = useRoute();
const cart = useCartStore();

const orderId = ref(route.query.order ?? null);
const order = ref(null);
const status = ref(orderId.value ? "success" : "loading"); // loading | success | error
const errorMessage = ref(null);

const paymentMethodLabels = {
  paypal: "PayPal",
  cash_on_delivery: "Cash on Delivery",
  paymongo: "PayMongo",
};

const paymentStatusLabels = {
  unpaid: "Pay upon delivery",
  paid: "Payment received",
};

async function loadOrder() {
  if (!orderId.value) {
    return;
  }

  const { data } = await ordersApi.show(orderId.value);
  order.value = data.order ?? data;
}

onMounted(async () => {
  // PayPal redirects back with ?token=<paypal_order_id>&PayerID=<payer_id>
  const paypalOrderId = route.query.token;

  if (!paypalOrderId) {
    // Not a PayPal return — already have an order ID from direct placement
    if (!orderId.value) {
      status.value = "error";
      errorMessage.value = "Missing order information.";
    } else {
      await loadOrder();
    }
    return;
  }

  // Capture the PayPal payment and create the order
  status.value = "loading";
  try {
    const storeId =
      cart.storeId ?? Number(sessionStorage.getItem("paypal_store_id"));
    const { data } = await paypalApi.captureOrder(paypalOrderId, storeId);

    orderId.value = data.order_id;
    order.value = data.order ?? null;
    status.value = "success";

    // Clean up
    sessionStorage.removeItem("paypal_store_id");
    cart.reset();
  } catch (e) {
    status.value = "error";
    errorMessage.value =
      e.response?.data?.message ??
      "Payment capture failed. Please contact support.";
  }
});
</script>

<template>
  <div class="flex flex-col items-center justify-center py-20 text-center">
    <template v-if="status === 'loading'">
      <div
        class="mb-4 size-16 animate-spin rounded-full border-4 border-brand-200 border-t-brand-500"
      />
      <h1 class="theme-title text-2xl font-bold">Processing Payment...</h1>
      <p class="theme-copy mt-2">
        Please wait while we confirm your payment with PayPal.
      </p>
    </template>

    <template v-else-if="status === 'success'">
      <CheckCircleIcon class="mb-4 size-16 text-green-500" />
      <h1 class="theme-title text-3xl font-bold">Order Confirmed!</h1>
      <p class="theme-copy mt-2">
        Thank you for your purchase. We'll notify you when it ships.
      </p>
      <p v-if="orderId" class="theme-copy mt-1 text-sm">
        Order #{{ orderId }}
      </p>
      <div
        v-if="order"
        class="theme-card-muted mt-4 rounded-2xl px-5 py-4 text-sm"
      >
        <p>
          Payment Method:
          <span class="theme-title font-semibold">
            {{ paymentMethodLabels[order.payment_method] ?? order.payment_method }}
          </span>
        </p>
        <p class="mt-1">
          Status:
          <span class="theme-title font-semibold">
            {{ paymentStatusLabels[order.payment_status] ?? order.payment_status }}
          </span>
        </p>
      </div>
      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-4">
        <RouterLink
          to="/account/orders"
          class="rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
        >
          View My Orders
        </RouterLink>
        <RouterLink
          to="/stores"
          class="btn-secondary rounded-xl px-6 py-3 text-sm font-semibold"
        >
          Continue Shopping
        </RouterLink>
      </div>
    </template>

    <template v-else-if="status === 'error'">
      <div
        class="mb-4 flex size-16 items-center justify-center rounded-full bg-red-500/12"
      >
        <span class="text-3xl text-red-500">!</span>
      </div>
      <h1 class="theme-title text-2xl font-bold">Payment Failed</h1>
      <p class="theme-copy mt-2 max-w-md">{{ errorMessage }}</p>
      <p class="theme-copy mt-1 text-sm">
        Your cart items are still saved. You can try placing the order again.
      </p>
      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-4">
        <RouterLink
          to="/checkout"
          class="rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
        >
          Try Again
        </RouterLink>
        <RouterLink
          to="/cart"
          class="btn-secondary rounded-xl px-6 py-3 text-sm font-semibold"
        >
          Return to Cart
        </RouterLink>
      </div>
    </template>
  </div>
</template>
