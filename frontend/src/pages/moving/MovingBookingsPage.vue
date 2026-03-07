<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { TruckIcon } from "@heroicons/vue/24/outline";
import { movingBookingsApi } from "@/api/movingBookings";

const bookings = ref([]);
const loading = ref(true);

const statusColors = {
  pending: "bg-yellow-100 text-yellow-700",
  confirmed: "bg-blue-100 text-blue-700",
  in_progress: "bg-indigo-100 text-indigo-700",
  completed: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};

const statusLabels = {
  pending: "Pending",
  confirmed: "Confirmed",
  in_progress: "In Progress",
  completed: "Completed",
  cancelled: "Cancelled",
};

function formatDate(dt) {
  return new Date(dt).toLocaleString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function formatPrice(centavos) {
  return (
    "₱" + (centavos / 100).toLocaleString("en-PH", { minimumFractionDigits: 2 })
  );
}

onMounted(async () => {
  try {
    const res = await movingBookingsApi.list();
    bookings.value = res.data.data ?? res.data;
  } catch {
    bookings.value = [];
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">My Moving Bookings</h1>
      <RouterLink
        :to="{ name: 'movers.index' }"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
      >
        Book a Move
      </RouterLink>
    </div>

    <div v-if="loading" class="space-y-4">
      <div
        v-for="i in 3"
        :key="i"
        class="h-24 animate-pulse rounded-xl bg-gray-200"
      ></div>
    </div>

    <div
      v-else-if="bookings.length === 0"
      class="py-16 text-center text-gray-500"
    >
      <TruckIcon class="mx-auto mb-4 h-16 w-16 text-gray-300" />
      <p class="text-lg font-medium">No moving bookings yet</p>
      <RouterLink
        :to="{ name: 'movers.index' }"
        class="mt-2 inline-block text-sm text-blue-600 underline"
      >
        Find a moving company
      </RouterLink>
    </div>

    <div v-else class="space-y-4">
      <RouterLink
        v-for="booking in bookings"
        :key="booking.id"
        :to="{ name: 'account.moving.show', params: { id: booking.id } }"
        class="block rounded-xl border bg-white p-5 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="font-semibold text-gray-900">
              {{ booking.store?.name ?? "Moving Company" }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              {{ booking.pickup_city }} → {{ booking.delivery_city }}
            </p>
            <p class="text-sm text-gray-500">
              {{ formatDate(booking.scheduled_at) }}
            </p>
          </div>
          <div class="text-right">
            <span
              class="inline-block rounded-full px-3 py-1 text-xs font-medium"
              :class="
                statusColors[booking.status] ?? 'bg-gray-100 text-gray-600'
              "
            >
              {{ statusLabels[booking.status] ?? booking.status }}
            </span>
            <p class="mt-2 text-sm font-semibold text-gray-700">
              {{ formatPrice(booking.total_price) }}
            </p>
          </div>
        </div>
      </RouterLink>
    </div>
  </div>
</template>
