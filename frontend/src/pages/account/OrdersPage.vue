<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { ordersApi } from "@/api/orders";

const orders = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await ordersApi.list();
    orders.value = data.data ?? data;
  } finally {
    loading.value = false;
  }
});

const statusColors = {
  pending: "bg-yellow-100 text-yellow-700",
  processing: "bg-blue-100 text-blue-700",
  dispatched: "bg-purple-100 text-purple-700",
  delivered: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold text-gray-900">My Orders</h1>

    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 4"
        :key="i"
        class="h-20 animate-pulse rounded-2xl bg-gray-100"
      />
    </div>

    <div
      v-else-if="orders.length === 0"
      class="py-12 text-center text-gray-400"
    >
      <p>No orders yet.</p>
      <RouterLink
        to="/stores"
        class="mt-3 inline-block text-sm font-medium text-brand-600 hover:underline"
      >
        Start shopping →
      </RouterLink>
    </div>

    <ul v-else class="space-y-3">
      <li
        v-for="order in orders"
        :key="order.id"
        class="rounded-2xl border bg-white p-4 shadow-sm"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="font-semibold text-gray-800">Order #{{ order.id }}</p>
            <p class="text-xs text-gray-400">{{ order.created_at }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span
              class="rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="statusColors[order.status] ?? 'bg-gray-100 text-gray-500'"
            >
              {{ order.status }}
            </span>
            <span class="font-semibold text-gray-900">{{
              order.total?.formatted
            }}</span>
            <RouterLink
              :to="`/account/orders/${order.id}`"
              class="text-xs font-medium text-brand-600 hover:underline"
            >
              View →
            </RouterLink>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
