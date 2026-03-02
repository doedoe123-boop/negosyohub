<script setup>
import { RouterLink, useRoute } from "vue-router";
import { CheckCircleIcon } from "@heroicons/vue/24/solid";

const route = useRoute();
const orderId = route.query.order;
</script>

<template>
  <div class="flex flex-col items-center justify-center py-20 text-center">
    <CheckCircleIcon class="mb-4 size-16 text-green-500" />
    <h1 class="text-3xl font-bold text-gray-900">Order Confirmed!</h1>
    <p class="mt-2 text-gray-500">
      Thank you for your purchase. We'll notify you when it ships.
    </p>
    <p v-if="orderId" class="mt-1 text-sm text-gray-400">
      Order #{{ orderId }}
    </p>
    <div class="mt-8 flex gap-4">
      <RouterLink
        to="/account/orders"
        class="rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
      >
        View My Orders
      </RouterLink>
      <RouterLink
        to="/stores"
        class="rounded-xl border px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
      >
        Continue Shopping
      </RouterLink>
    </div>
  </div>
</template>
