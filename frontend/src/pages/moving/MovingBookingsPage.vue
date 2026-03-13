<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  TruckIcon,
  CalendarIcon,
  MapPinIcon,
  ArrowRightIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
  ChevronRightIcon
} from "@heroicons/vue/24/outline";
import { movingBookingsApi } from "@/api/movingBookings";

const bookings = ref([]);
const loading = ref(true);
const error = ref(false);

const statusConfig = {
  pending: {
    label: "Pending",
    icon: ClockIcon,
    colorClass: "bg-amber-50 text-amber-700 border-amber-100",
    dotClass: "bg-amber-400"
  },
  confirmed: {
    label: "Confirmed",
    icon: CheckCircleIcon,
    colorClass: "bg-blue-50 text-blue-700 border-blue-100",
    dotClass: "bg-blue-400"
  },
  in_progress: {
    label: "In Progress",
    icon: ArrowPathIcon,
    colorClass: "bg-indigo-50 text-indigo-700 border-indigo-100",
    dotClass: "bg-indigo-400"
  },
  completed: {
    label: "Completed",
    icon: CheckCircleIcon,
    colorClass: "bg-emerald-50 text-emerald-700 border-emerald-100",
    dotClass: "bg-emerald-400"
  },
  cancelled: {
    label: "Cancelled",
    icon: XCircleIcon,
    colorClass: "bg-rose-50 text-rose-700 border-rose-100",
    dotClass: "bg-rose-400"
  },
};

function formatDate(dt) {
  if (!dt) return "—";
  return new Date(dt).toLocaleString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function formatPrice(centavos) {
  if (centavos == null) return "—";
  return (centavos / 100).toLocaleString("en-PH", {
    style: "currency",
    currency: "PHP"
  });
}

const loadBookings = async () => {
  loading.value = true;
  error.value = false;
  try {
    const res = await movingBookingsApi.list();
    bookings.value = res.data.data ?? res.data;
  } catch (err) {
    error.value = true;
    bookings.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadBookings();
});
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <!-- Header Area -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
          My Moving Bookings
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Track and manage your upcoming and past Lipat Bahay moves.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'movers.index' }"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm ring-1 ring-brand-700 transition-all hover:bg-brand-700 hover:shadow-md active:scale-[0.98]"
      >
        <TruckIcon class="size-4.5" />
        Book a Move
      </RouterLink>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="space-y-4">
      <div
        v-for="i in 3"
        :key="i"
        class="h-32 animate-pulse rounded-2xl bg-white ring-1 ring-slate-200"
      />
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="rounded-2xl border border-rose-100 bg-rose-50 p-8 text-center"
    >
      <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-100">
        <XCircleIcon class="size-6 text-rose-600" />
      </div>
      <h3 class="mt-3 text-sm font-bold text-rose-900">Failed to load bookings</h3>
      <p class="mt-1 text-xs text-rose-600">Please try refreshing the page or contact support.</p>
      <button 
        @click="loadBookings"
        class="mt-4 text-xs font-bold text-rose-700 underline underline-offset-4 hover:text-rose-900"
      >
        Try again
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="bookings.length === 0"
      class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white py-16 px-6 text-center shadow-sm"
    >
      <div class="relative mb-6">
        <div class="absolute -inset-4 rounded-full bg-brand-50 opacity-50 blur-xl animate-pulse"></div>
        <div class="relative flex size-20 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 shadow-inner ring-1 ring-brand-200">
          <TruckIcon class="size-10" />
        </div>
      </div>
      <h3 class="text-xl font-bold text-slate-900 tracking-tight">Ready for your next move?</h3>
      <p class="mx-auto mt-2 max-w-xs text-sm text-slate-500">
        You don't have any bookings yet. Browse our trusted network of moving professionals.
      </p>
      <RouterLink
        :to="{ name: 'movers.index' }"
        class="mt-8 inline-flex items-center gap-1.5 text-sm font-bold text-brand-600 hover:text-brand-700"
      >
        Find professional movers
        <ChevronRightIcon class="size-4 transition-transform group-hover:translate-x-1" />
      </RouterLink>
    </div>

    <!-- Bookings List -->
    <div v-else class="grid gap-5">
      <RouterLink
        v-for="booking in bookings"
        :key="booking.id"
        :to="{ name: 'account.moving.show', params: { id: booking.id } }"
        class="group relative block overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-brand-200 hover:shadow-md active:scale-[0.995]"
      >
        <!-- Status Badge -->
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-400 ring-1 ring-slate-100 shadow-sm group-hover:bg-brand-50 group-hover:text-brand-600 group-hover:ring-brand-100 transition-colors">
              <TruckIcon class="size-6" />
            </div>
            <div class="min-w-0">
              <h3 class="truncate text-base font-bold text-slate-900 group-hover:text-brand-700 transition-colors">
                {{ booking.store?.name ?? 'Moving Company' }}
              </h3>
              <div class="flex items-center gap-1.5 text-xs text-slate-500">
                 <CalendarIcon class="size-3.5" />
                 {{ formatDate(booking.scheduled_at) }}
              </div>
            </div>
          </div>
          
          <div class="flex flex-col items-end gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider shadow-sm"
              :class="statusConfig[booking.status]?.colorClass ?? 'bg-slate-50 text-slate-700 border-slate-200'"
            >
              <span class="size-1.5 rounded-full" :class="statusConfig[booking.status]?.dotClass ?? 'bg-slate-400'"></span>
              {{ statusConfig[booking.status]?.label ?? booking.status }}
            </span>
            <p class="text-sm font-extrabold text-slate-900">
              {{ formatPrice(booking.total_price) }}
            </p>
          </div>
        </div>

        <!-- Route Info -->
        <div class="mt-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
          <div class="min-w-0">
            <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Pickup</p>
            <p class="mt-0.5 truncate text-sm font-semibold text-slate-700">{{ booking.pickup_city }}</p>
          </div>
          
          <div class="flex size-8 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-slate-200">
            <ArrowRightIcon class="size-4 text-brand-600" />
          </div>

          <div class="min-w-0 text-right">
            <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Delivery</p>
            <p class="mt-0.5 truncate text-sm font-semibold text-slate-700">{{ booking.delivery_city }}</p>
          </div>
        </div>
        
        <!-- Hover indicator -->
        <div class="absolute inset-y-0 right-0 w-1 bg-brand-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
      </RouterLink>
    </div>
  </div>
</template>
