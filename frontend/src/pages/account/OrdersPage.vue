<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { ordersApi } from "@/api/orders";

const orders = ref([]);
const loading = ref(true);
const error = ref(false);

onMounted(async () => {
  try {
    const { data } = await ordersApi.list();
    orders.value = data.data ?? data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

const statusColors = {
  pending: "bg-yellow-100 text-yellow-700",
  confirmed: "bg-blue-100 text-blue-700",
  preparing: "bg-purple-100 text-purple-700",
  ready: "bg-indigo-100 text-indigo-700",
  delivered: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-0">
    <h1 class="theme-title mb-6 text-2xl font-extrabold tracking-tight">
      My Orders
    </h1>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="theme-skeleton h-20 animate-pulse rounded-2xl" />
    </div>

    <div
      v-else-if="error"
      class="rounded-2xl border border-red-100 bg-red-50 py-10 text-center text-sm text-red-600"
    >
      Failed to load orders. Please refresh the page.
    </div>

    <div
      v-else-if="orders.length === 0"
      class="theme-empty-state rounded-2xl py-12 text-center"
    >
      <p class="theme-copy font-medium">No orders yet.</p>
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
        class="theme-card rounded-2xl p-4"
      >
        <div
          class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center justify-between gap-3 sm:block">
            <p class="theme-title font-semibold">Order #{{ order.id }}</p>
            <span
              class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize sm:hidden"
              :class="
                statusColors[order.status] ?? 'theme-badge-neutral'
              "
            >
              {{ order.status }}
            </span>
          </div>
          <p class="theme-copy text-xs sm:hidden">{{ order.created_at }}</p>
          <div class="flex items-center gap-3">
            <span
              class="hidden rounded-full px-2.5 py-0.5 text-xs font-medium capitalize sm:inline-block"
              :class="
                statusColors[order.status] ?? 'theme-badge-neutral'
              "
            >
              {{ order.status }}
            </span>
            <p class="theme-copy hidden text-xs sm:block">
              {{ order.created_at }}
            </p>
            <span class="theme-title font-bold">{{
              order.total?.formatted
            }}</span>
            <RouterLink
              :to="`/account/orders/${order.id}`"
              class="btn-secondary rounded-lg px-3 py-1.5 text-xs font-medium text-brand-600 transition-colors"
            >
              View →
            </RouterLink>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
