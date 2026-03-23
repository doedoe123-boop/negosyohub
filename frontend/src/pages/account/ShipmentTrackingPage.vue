<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  TruckIcon,
  MapPinIcon,
} from "@heroicons/vue/24/outline";
import { ordersApi } from "@/api/orders";

const route = useRoute();
const router = useRouter();

const order = ref(null);
const loading = ref(true);

const deliverySteps = [
  { key: "pending", label: "Preparing" },
  { key: "awaiting_booking", label: "Awaiting Booking" },
  { key: "driver_assigned", label: "Ready for Pickup" },
  { key: "picked_up", label: "Picked Up" },
  { key: "in_transit", label: "Out for Delivery" },
  { key: "delivered", label: "Delivered" },
];

const deliveryStatusOrder = deliverySteps.map((step) => step.key);

const shipment = computed(() => order.value?.latest_shipment ?? null);

const currentStepIndex = computed(() => {
  const status = shipment.value?.delivery_status;
  return status ? deliveryStatusOrder.indexOf(status) : -1;
});

function stepState(index) {
  if (index < currentStepIndex.value) {
    return "done";
  }

  if (index === currentStepIndex.value) {
    return "active";
  }

  return "upcoming";
}

function formatDate(dateStr) {
  if (!dateStr) return "—";

  return new Date(dateStr).toLocaleString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

onMounted(async () => {
  try {
    const { data } = await ordersApi.show(route.params.id);
    order.value = data.order ?? data;

    if (!order.value?.latest_shipment) {
      router.push({ name: "account.orders.show", params: { id: route.params.id } });
    }
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="theme-page mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <RouterLink
      :to="{ name: 'account.orders.show', params: { id: route.params.id } }"
      class="theme-copy group mb-5 inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:text-brand-600"
    >
      <ArrowLeftIcon class="size-4 transition-transform group-hover:-translate-x-1" />
      Back to Order
    </RouterLink>

    <div v-if="loading" class="space-y-4">
      <div class="theme-card h-24 animate-pulse rounded-3xl" />
      <div class="theme-card h-56 animate-pulse rounded-3xl" />
    </div>

    <template v-else-if="order && shipment">
      <div class="theme-card mb-5 rounded-3xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="theme-copy text-xs font-semibold uppercase tracking-[0.25em]">
              Shipment Tracking
            </p>
            <h1 class="theme-title mt-2 text-2xl font-extrabold tracking-tight">
              Order #{{ order.id }}
            </h1>
            <p class="theme-copy mt-2 text-sm">
              {{ shipment.customer_delivery_label }}
            </p>
          </div>
          <div class="theme-card-muted rounded-2xl px-4 py-3 text-right">
            <p class="theme-copy text-[11px] font-semibold uppercase tracking-wide">
              Tracking Reference
            </p>
            <p class="theme-title mt-1 text-sm font-bold">
              {{ shipment.external_reference || `SHIP-${order.id}` }}
            </p>
          </div>
        </div>
      </div>

      <div class="theme-card mb-5 rounded-3xl p-6">
        <div class="mb-5 flex items-center gap-2">
          <TruckIcon class="size-5 text-brand-600" />
          <h2 class="theme-title text-base font-bold">Delivery Progress</h2>
        </div>

        <ol class="flex flex-wrap items-start gap-0">
          <li
            v-for="(step, i) in deliverySteps"
            :key="step.key"
            class="flex flex-1 flex-col items-center"
          >
            <div class="flex w-full items-center">
              <div
                v-if="i > 0"
                class="h-0.5 flex-1"
                :class="stepState(i) === 'done' || stepState(i) === 'active' ? 'bg-brand-500' : 'theme-skeleton'"
              />
              <div
                class="flex size-8 shrink-0 items-center justify-center rounded-full border-2"
                :class="{
                  'border-brand-500 bg-brand-500': stepState(i) === 'done',
                  'border-brand-500 bg-[var(--color-surface)] ring-4 ring-brand-100': stepState(i) === 'active',
                  'theme-divider bg-[var(--color-surface)]': stepState(i) === 'upcoming',
                }"
              >
                <CheckCircleIcon v-if="stepState(i) === 'done'" class="size-4 text-white" />
                <div v-else-if="stepState(i) === 'active'" class="size-2.5 rounded-full bg-brand-500" />
              </div>
              <div
                v-if="i < deliverySteps.length - 1"
                class="h-0.5 flex-1"
                :class="stepState(i) === 'done' ? 'bg-brand-500' : 'theme-skeleton'"
              />
            </div>
            <p class="theme-copy mt-2 text-center text-[11px] font-medium">
              {{ step.label }}
            </p>
          </li>
        </ol>

        <div class="theme-divider-soft mt-6 grid gap-4 border-t pt-5 sm:grid-cols-2">
          <div>
            <p class="theme-copy text-[11px] font-semibold uppercase tracking-wide">
              Driver
            </p>
            <p class="theme-title mt-1 text-sm font-semibold">
              {{ shipment.driver_name || "Dispatching in progress" }}
            </p>
            <p v-if="shipment.driver_contact" class="theme-copy text-xs">
              {{ shipment.driver_contact }}
            </p>
          </div>
          <div>
            <p class="theme-copy text-[11px] font-semibold uppercase tracking-wide">
              Vehicle
            </p>
            <p class="theme-title mt-1 text-sm font-semibold">
              {{ shipment.vehicle_type || "To be assigned" }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div class="theme-card rounded-3xl p-6">
          <div class="mb-4 flex items-center gap-2">
            <MapPinIcon class="size-5 text-brand-600" />
            <h2 class="theme-title text-base font-bold">Pickup</h2>
          </div>
          <p class="theme-copy text-sm leading-relaxed">
            {{ shipment.pickup_address || "Preparing pickup details." }}
          </p>
          <p class="theme-copy mt-4 text-xs">
            Booked: {{ formatDate(shipment.booked_at) }}
          </p>
          <p class="theme-copy mt-1 text-xs">
            Picked up: {{ formatDate(shipment.picked_up_at) }}
          </p>
        </div>

        <div class="theme-card rounded-3xl p-6">
          <div class="mb-4 flex items-center gap-2">
            <MapPinIcon class="size-5 text-emerald-500" />
            <h2 class="theme-title text-base font-bold">Drop-off</h2>
          </div>
          <p class="theme-copy text-sm leading-relaxed">
            {{ shipment.dropoff_address || "Preparing drop-off details." }}
          </p>
          <p class="theme-copy mt-4 text-xs">
            Delivered: {{ formatDate(shipment.delivered_at) }}
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
