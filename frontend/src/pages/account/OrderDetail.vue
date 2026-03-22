<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import {
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from "@heroicons/vue/24/solid";
import {
  ArrowPathIcon,
  ShoppingBagIcon,
  MapPinIcon,
  CreditCardIcon,
  BuildingStorefrontIcon,
} from "@heroicons/vue/24/outline";
import { ordersApi } from "@/api/orders";
import { cartApi } from "@/api/cart";

const route = useRoute();
const router = useRouter();
const order = ref(null);
const loading = ref(true);
const cancelling = ref(false);
const reordering = ref(false);
const reorderError = ref(false);
const showCancelModal = ref(false);

onMounted(async () => {
  try {
    const { data } = await ordersApi.show(route.params.id);
    order.value = data.order ?? data;
  } finally {
    loading.value = false;
  }
});

const steps = [
  { key: "pending", label: "Order Placed" },
  { key: "confirmed", label: "Confirmed" },
  { key: "preparing", label: "Preparing" },
  { key: "shipped", label: "Shipped" },
  { key: "delivered", label: "Delivered" },
];

const statusOrder = [
  "pending",
  "confirmed",
  "preparing",
  "shipped",
  "delivered",
];

const currentStepIndex = computed(() => {
  const status = order.value?.status;

  if (status === "cancelled") {
    return -1;
  }

  if (status === "ready") {
    return statusOrder.indexOf("shipped");
  }

  return statusOrder.indexOf(status);
});

function stepState(index) {
  if (order.value?.status === "cancelled") {
    return "cancelled";
  }

  if (index < currentStepIndex.value) {
    return "done";
  }

  if (index === currentStepIndex.value) {
    return "active";
  }

  return "upcoming";
}

const statusColors = {
  pending: "bg-yellow-100 text-yellow-700 border-yellow-200",
  confirmed: "bg-blue-100 text-blue-700 border-blue-200",
  preparing: "bg-purple-100 text-purple-700 border-purple-200",
  shipped: "bg-indigo-100 text-indigo-700 border-indigo-200",
  ready: "bg-indigo-100 text-indigo-700 border-indigo-200",
  delivered: "bg-green-100 text-green-700 border-green-200",
  cancelled: "bg-red-100 text-red-700 border-red-200",
};

const paymentStatusColors = {
  unpaid: "bg-yellow-100 text-yellow-700",
  pending: "bg-yellow-100 text-yellow-700",
  paid: "bg-green-100 text-green-700",
  failed: "bg-red-100 text-red-600",
  refunded: "theme-badge-neutral",
};

const paymentStatusLabels = {
  unpaid: "Unpaid",
  pending: "Unpaid",
  paid: "Paid",
  failed: "Failed",
  refunded: "Refunded",
};

const paymentMethodLabels = {
  paypal: "PayPal",
  paymongo: "PayMongo",
  cash_on_delivery: "Cash on Delivery",
};

const paymentStatusHelper = {
  unpaid: "Pay upon delivery",
  pending: "Awaiting payment",
  paid: "Payment received",
  failed: "Payment failed",
  refunded: "Refund issued",
};

const deliverySteps = [
  { key: "pending", label: "Preparing" },
  { key: "awaiting_booking", label: "Awaiting Booking" },
  { key: "driver_assigned", label: "Ready for Pickup" },
  { key: "picked_up", label: "Picked Up" },
  { key: "in_transit", label: "Out for Delivery" },
  { key: "delivered", label: "Delivered" },
];

const deliveryStatusOrder = deliverySteps.map((step) => step.key);

const currentDeliveryStepIndex = computed(() => {
  const status = order.value?.latest_shipment?.delivery_status;
  return status ? deliveryStatusOrder.indexOf(status) : -1;
});

function deliveryStepState(index) {
  if (index < currentDeliveryStepIndex.value) {
    return "done";
  }

  if (index === currentDeliveryStepIndex.value) {
    return "active";
  }

  return "upcoming";
}

function formatStatusLabel(status) {
  if (status === "ready") return "shipped";
  return status;
}

const productLines = computed(() =>
  (order.value?.lines ?? []).filter((l) => l.type !== "shipping"),
);

const shippingLine = computed(() =>
  (order.value?.lines ?? []).find((l) => l.type === "shipping"),
);

const shippingAddress = computed(() => {
  const addrs = order.value?.addresses ?? [];
  return addrs.find((a) => a.type === "shipping") ?? addrs[0] ?? null;
});

function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function cancelOrder() {
  cancelling.value = true;

  try {
    const { data } = await ordersApi.cancel(order.value.id);
    order.value = data.order ?? data;
  } finally {
    cancelling.value = false;
    showCancelModal.value = false;
  }
}

async function reorder() {
  reordering.value = true;
  reorderError.value = false;

  try {
    for (const line of productLines.value) {
      await cartApi.addItem("product", line.purchasable_id, line.quantity);
    }
    router.push("/cart");
  } catch {
    reorderError.value = true;
  } finally {
    reordering.value = false;
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-0">
    <RouterLink
      to="/account/orders"
      class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition-colors hover:text-brand-700"
    >
      ← My Orders
    </RouterLink>

    <!-- Skeleton loading -->
    <div v-if="loading" class="space-y-4">
      <div class="theme-skeleton h-8 w-48 animate-pulse rounded-lg" />
      <div class="theme-skeleton h-28 animate-pulse rounded-2xl" />
      <div class="theme-skeleton h-48 animate-pulse rounded-2xl" />
    </div>

    <div v-else-if="order">
      <!-- Header -->
      <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="theme-title text-2xl font-extrabold tracking-tight">
            Order #{{ order.id }}
          </h1>
          <p class="theme-copy mt-1 text-sm">
            {{ formatDate(order.created_at) }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span
            class="rounded-full border px-3 py-1 text-xs font-semibold capitalize"
            :class="statusColors[order.status] ?? 'theme-badge-neutral'"
          >
            {{ formatStatusLabel(order.status) }}
          </span>
          <span
            v-if="order.payment_status"
            class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
            :class="
              paymentStatusColors[order.payment_status] ??
              'theme-badge-neutral'
            "
          >
            Payment: {{
              paymentStatusLabels[order.payment_status] ?? order.payment_status
            }}
          </span>
        </div>
      </div>

      <!-- Status timeline (not shown if cancelled) -->
      <div
        v-if="order.status !== 'cancelled'"
        class="theme-card mb-6 rounded-2xl p-5"
      >
        <ol class="flex items-start gap-0">
          <li
            v-for="(step, i) in steps"
            :key="step.key"
            class="flex flex-1 flex-col items-center"
          >
            <div class="flex w-full items-center">
              <div
                v-if="i > 0"
                class="h-0.5 flex-1 transition-colors"
                :class="
                  stepState(i) === 'done' || stepState(i) === 'active'
                    ? 'bg-brand-500'
                    : 'theme-skeleton'
                "
              />
              <div
                class="flex size-7 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                :class="{
                  'border-brand-500 bg-brand-500': stepState(i) === 'done',
                  'border-brand-500 bg-[var(--color-surface)] ring-4 ring-brand-100':
                    stepState(i) === 'active',
                  'theme-divider bg-[var(--color-surface)]': stepState(i) === 'upcoming',
                }"
              >
                <CheckCircleIcon
                  v-if="stepState(i) === 'done'"
                  class="size-4 text-white"
                />
                <div
                  v-else-if="stepState(i) === 'active'"
                  class="size-2.5 rounded-full bg-brand-500"
                />
              </div>
              <div
                v-if="i < steps.length - 1"
                class="h-0.5 flex-1 transition-colors"
                :class="
                  stepState(i) === 'done' ? 'bg-brand-500' : 'theme-skeleton'
                "
              />
            </div>
            <p
              class="mt-2 text-center text-xs font-medium leading-tight"
              :class="{
                'font-bold text-brand-700': stepState(i) === 'active',
                'theme-copy': stepState(i) === 'done' || stepState(i) === 'upcoming',
              }"
            >
              {{ step.label }}
            </p>
          </li>
        </ol>
      </div>

      <!-- Cancelled banner -->
      <div
        v-else
        class="mb-6 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      >
        <XCircleIcon class="size-5 shrink-0" />
        <div>
          <p class="font-semibold">This order was cancelled</p>
          <p v-if="order.cancelled_at" class="mt-0.5 text-xs text-red-500">
            {{ formatDate(order.cancelled_at) }}
          </p>
        </div>
      </div>

      <!-- Store info -->
      <div
        v-if="order.store"
        class="theme-card mb-4 flex items-center gap-3 rounded-2xl px-5 py-3"
      >
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
        >
          <BuildingStorefrontIcon class="size-5" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="theme-title text-sm font-semibold">
            {{ order.store.name }}
          </p>
        </div>
      </div>

      <div
        v-if="order.latest_shipment"
        class="theme-card mb-4 rounded-2xl p-5"
      >
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <h2 class="theme-title text-sm font-bold">Delivery Progress</h2>
            <p class="theme-copy mt-1 text-xs">
              {{ order.latest_shipment.customer_delivery_label }}
            </p>
          </div>
          <a
            v-if="order.latest_shipment.tracking_url"
            :href="order.latest_shipment.tracking_url"
            target="_blank"
            rel="noreferrer"
            class="text-sm font-semibold text-brand-600 hover:underline"
          >
            Track shipment
          </a>
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
                :class="
                  deliveryStepState(i) === 'done' || deliveryStepState(i) === 'active'
                    ? 'bg-brand-500'
                    : 'theme-skeleton'
                "
              />
              <div
                class="flex size-7 shrink-0 items-center justify-center rounded-full border-2"
                :class="{
                  'border-brand-500 bg-brand-500': deliveryStepState(i) === 'done',
                  'border-brand-500 bg-[var(--color-surface)] ring-4 ring-brand-100':
                    deliveryStepState(i) === 'active',
                  'theme-divider bg-[var(--color-surface)]': deliveryStepState(i) === 'upcoming',
                }"
              >
                <CheckCircleIcon
                  v-if="deliveryStepState(i) === 'done'"
                  class="size-4 text-white"
                />
                <div
                  v-else-if="deliveryStepState(i) === 'active'"
                  class="size-2.5 rounded-full bg-brand-500"
                />
              </div>
              <div
                v-if="i < deliverySteps.length - 1"
                class="h-0.5 flex-1"
                :class="deliveryStepState(i) === 'done' ? 'bg-brand-500' : 'theme-skeleton'"
              />
            </div>
            <p class="theme-copy mt-2 text-center text-[11px] font-medium">
              {{ step.label }}
            </p>
          </li>
        </ol>
        <div class="theme-divider-soft mt-4 grid gap-3 border-t pt-4 sm:grid-cols-2">
          <div v-if="order.latest_shipment.driver_name">
            <p class="theme-copy text-[11px] font-semibold uppercase tracking-wide">Driver</p>
            <p class="theme-title text-sm font-semibold">
              {{ order.latest_shipment.driver_name }}
            </p>
            <p v-if="order.latest_shipment.driver_contact" class="theme-copy text-xs">
              {{ order.latest_shipment.driver_contact }}
            </p>
          </div>
          <div v-if="order.latest_shipment.vehicle_type">
            <p class="theme-copy text-[11px] font-semibold uppercase tracking-wide">Vehicle</p>
            <p class="theme-title text-sm font-semibold">
              {{ order.latest_shipment.vehicle_type }}
            </p>
          </div>
        </div>
      </div>

      <!-- Line items -->
      <div class="theme-card mb-4 rounded-2xl shadow-sm">
        <div
          class="theme-divider-soft flex items-center gap-2 border-b px-5 py-3"
        >
          <ShoppingBagIcon class="theme-copy size-4" />
          <h2 class="theme-title text-sm font-bold">
            Items ({{ productLines.length }})
          </h2>
        </div>
        <ul class="theme-divider-soft divide-y">
          <li
            v-for="line in productLines"
            :key="line.id"
            class="flex items-center gap-4 px-5 py-4"
          >
            <div
              class="theme-card-muted size-14 shrink-0 overflow-hidden rounded-lg"
            >
              <img
                v-if="line.thumbnail"
                :src="line.thumbnail"
                :alt="line.description"
                class="size-full object-cover"
              />
              <div
                v-else
                class="theme-copy flex size-full items-center justify-center"
              >
                <ShoppingBagIcon class="size-6" />
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <p class="theme-title text-sm font-medium leading-snug">
                {{ line.description || "Item" }}
              </p>
              <p class="theme-copy mt-0.5 text-xs">
                Qty: {{ line.quantity }}
                <span v-if="line.unit_price?.formatted">
                  &middot; {{ line.unit_price.formatted }} each
                </span>
              </p>
            </div>
            <span class="theme-title shrink-0 text-sm font-semibold">
              {{ line.sub_total?.formatted }}
            </span>
          </li>
          <li
            v-if="!productLines.length"
            class="theme-copy px-5 py-6 text-center text-sm"
          >
            No item details available.
          </li>
        </ul>

        <!-- Price breakdown -->
        <div class="theme-divider-soft theme-copy space-y-2 border-t px-5 py-4 text-sm">
          <div
            v-if="order.sub_total"
            class="flex justify-between"
          >
            <span>Subtotal</span>
            <span>{{ order.sub_total.formatted }}</span>
          </div>
          <div v-if="shippingLine" class="flex justify-between">
            <span>{{ shippingLine.description }}</span>
            <span>{{
              shippingLine.total?.formatted ?? order.shipping_total?.formatted
            }}</span>
          </div>
          <div
            v-else-if="
              order.shipping_total &&
              order.shipping_total.value &&
              order.shipping_total.value > 0
            "
            class="flex justify-between"
          >
            <span>Shipping</span>
            <span>{{ order.shipping_total.formatted }}</span>
          </div>
          <div
            v-if="
              order.tax_total &&
              order.tax_total.value &&
              order.tax_total.value > 0
            "
            class="flex justify-between"
          >
            <span>Tax</span>
            <span>{{ order.tax_total.formatted }}</span>
          </div>
          <div
            v-if="
              order.discount_total &&
              order.discount_total.value &&
              order.discount_total.value > 0
            "
            class="flex justify-between text-emerald-500"
          >
            <span>{{ order.meta?.applied_coupon?.code ?? "Discount" }}</span>
            <span>-{{ order.discount_total.formatted }}</span>
          </div>
          <div
            class="theme-divider-soft theme-title flex justify-between border-t pt-2 font-bold"
          >
            <span>Total</span>
            <span class="text-lg">{{ order.total?.formatted }}</span>
          </div>
        </div>
      </div>

      <!-- Shipping address -->
      <div
        v-if="shippingAddress"
        class="theme-card mb-4 rounded-2xl shadow-sm"
      >
        <div
          class="theme-divider-soft flex items-center gap-2 border-b px-5 py-3"
        >
          <MapPinIcon class="theme-copy size-4" />
          <h2 class="theme-title text-sm font-bold">Delivery Address</h2>
        </div>
        <div class="theme-copy px-5 py-4 text-sm">
          <p class="theme-title font-medium">
            {{ shippingAddress.first_name }}
            {{ shippingAddress.last_name }}
          </p>
          <p>{{ shippingAddress.line_one }}</p>
          <p v-if="shippingAddress.line_two">
            {{ shippingAddress.line_two }}
          </p>
          <p>
            {{ shippingAddress.city }},
            {{ shippingAddress.state }}
            {{ shippingAddress.postcode }}
          </p>
          <p v-if="shippingAddress.contact_phone" class="theme-copy mt-1">
            {{ shippingAddress.contact_phone }}
          </p>
        </div>
      </div>

      <!-- Payment info -->
      <div
        v-if="order.payment_status"
        class="theme-card mb-6 rounded-2xl shadow-sm"
      >
        <div
          class="theme-divider-soft flex items-center gap-2 border-b px-5 py-3"
        >
          <CreditCardIcon class="theme-copy size-4" />
          <h2 class="theme-title text-sm font-bold">Payment</h2>
        </div>
        <div class="theme-copy px-5 py-4 text-sm">
          <div class="flex items-center gap-2">
            <span class="theme-title font-medium">
              {{
                paymentMethodLabels[order.payment_method] ??
                order.payment_method ??
                "Payment"
              }}
            </span>
            <span class="theme-copy">•</span>
            <span class="theme-title font-medium">
              {{
                paymentStatusLabels[order.payment_status] ?? order.payment_status
              }}
            </span>
            <span v-if="order.paid_at" class="theme-copy text-xs">
              &middot; {{ formatDate(order.paid_at) }}
            </span>
          </div>
          <p v-if="order.payment_status" class="theme-copy mt-1 text-xs">
            {{
              paymentStatusHelper[order.payment_status] ??
              "Payment status updated."
            }}
          </p>
          <p v-if="order.payment_intent_id" class="theme-copy mt-1 text-xs">
            Ref: {{ order.payment_intent_id }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3">
        <button
          v-if="order.status === 'pending'"
          class="rounded-xl border border-red-300 px-5 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 disabled:opacity-60"
          @click="showCancelModal = true"
        >
          Cancel Order
        </button>

        <button
          v-if="order.lines?.length"
          class="btn-secondary flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-medium transition-colors disabled:opacity-60"
          :disabled="reordering"
          @click="reorder"
        >
          <ArrowPathIcon class="size-4" />
          {{ reordering ? "Adding to cart…" : "Reorder" }}
        </button>
        <p v-if="reorderError" class="mt-2 w-full text-xs text-red-600">
          Failed to add items to cart. Please try again.
        </p>
      </div>
    </div>

    <!-- Cancel confirmation modal -->
    <Teleport to="body">
      <div
        v-if="showCancelModal"
        class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showCancelModal = false"
      >
        <div class="theme-modal w-full max-w-sm rounded-2xl p-6 shadow-xl" @click.stop>
          <div class="mb-4 flex items-center gap-3">
            <div
              class="flex size-10 items-center justify-center rounded-full bg-red-100"
            >
              <ExclamationTriangleIcon class="size-5 text-red-600" />
            </div>
            <h3 class="theme-title text-lg font-bold">Cancel Order?</h3>
          </div>
          <p class="theme-copy mb-6 text-sm">
            Are you sure you want to cancel
            <strong>Order #{{ order?.id }}</strong
            >? This action cannot be undone. If payment was already processed, a
            refund will be initiated.
          </p>
          <div class="flex justify-end gap-3">
            <button
              class="btn-secondary rounded-xl px-4 py-2 text-sm font-medium transition-colors"
              @click="showCancelModal = false"
            >
              Keep Order
            </button>
            <button
              :disabled="cancelling"
              class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
              @click="cancelOrder"
            >
              {{ cancelling ? "Cancelling…" : "Yes, Cancel Order" }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
