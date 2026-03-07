<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  TruckIcon,
  MapPinIcon,
  CalendarIcon,
  StarIcon,
} from "@heroicons/vue/24/outline";
import { movingBookingsApi } from "@/api/movingBookings";

const route = useRoute();
const router = useRouter();

const booking = ref(null);
const loading = ref(true);
const cancelLoading = ref(false);
const reviewLoading = ref(false);
const reviewSuccess = ref(false);

const reviewForm = ref({ rating: 5, comment: "" });

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

const canCancel = computed(() => booking.value?.status === "pending");

const canReview = computed(
  () => booking.value?.status === "completed" && !booking.value?.review,
);

function formatDate(dt) {
  return new Date(dt).toLocaleString("en-PH", {
    month: "long",
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

async function cancelBooking() {
  if (!confirm("Are you sure you want to cancel this booking?")) return;
  cancelLoading.value = true;
  try {
    await movingBookingsApi.cancel(booking.value.id);
    booking.value.status = "cancelled";
  } finally {
    cancelLoading.value = false;
  }
}

async function submitReview() {
  reviewLoading.value = true;
  try {
    await movingBookingsApi.submitReview(booking.value.id, reviewForm.value);
    reviewSuccess.value = true;
    booking.value.review = reviewForm.value;
  } finally {
    reviewLoading.value = false;
  }
}

onMounted(async () => {
  try {
    const res = await movingBookingsApi.show(route.params.id);
    booking.value = res.data;
  } catch {
    router.push({ name: "account.moving" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-16">
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
      ></div>
    </div>

    <template v-else-if="booking">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <button
            class="mb-2 text-sm text-blue-600 underline"
            @click="router.push({ name: 'account.moving' })"
          >
            ← Back to Bookings
          </button>
          <h1 class="text-2xl font-bold text-gray-900">
            Booking #{{ booking.id }}
          </h1>
        </div>
        <span
          class="rounded-full px-4 py-1.5 text-sm font-medium"
          :class="statusColors[booking.status] ?? 'bg-gray-100 text-gray-600'"
        >
          {{ statusLabels[booking.status] ?? booking.status }}
        </span>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Details card -->
        <div class="rounded-xl border bg-white p-6 shadow-sm">
          <h2 class="mb-4 font-semibold text-gray-900">Move Details</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex items-start gap-2">
              <TruckIcon class="mt-0.5 h-4 w-4 text-gray-400 shrink-0" />
              <div>
                <dt class="text-gray-500">Moving Company</dt>
                <dd class="font-medium">{{ booking.store?.name }}</dd>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <CalendarIcon class="mt-0.5 h-4 w-4 text-gray-400 shrink-0" />
              <div>
                <dt class="text-gray-500">Scheduled</dt>
                <dd class="font-medium">
                  {{ formatDate(booking.scheduled_at) }}
                </dd>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <MapPinIcon class="mt-0.5 h-4 w-4 text-gray-400 shrink-0" />
              <div>
                <dt class="text-gray-500">From</dt>
                <dd class="font-medium">
                  {{ booking.pickup_address }}, {{ booking.pickup_city }}
                </dd>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <MapPinIcon class="mt-0.5 h-4 w-4 text-gray-400 shrink-0" />
              <div>
                <dt class="text-gray-500">To</dt>
                <dd class="font-medium">
                  {{ booking.delivery_address }}, {{ booking.delivery_city }}
                </dd>
              </div>
            </div>
            <div v-if="booking.notes">
              <dt class="text-gray-500">Notes</dt>
              <dd class="font-medium">{{ booking.notes }}</dd>
            </div>
          </dl>
        </div>

        <!-- Pricing card -->
        <div class="rounded-xl border bg-white p-6 shadow-sm">
          <h2 class="mb-4 font-semibold text-gray-900">Pricing</h2>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Base Price</dt>
              <dd>{{ formatPrice(booking.base_price) }}</dd>
            </div>
            <div v-if="booking.add_ons_total > 0" class="flex justify-between">
              <dt class="text-gray-500">Add-ons</dt>
              <dd>{{ formatPrice(booking.add_ons_total) }}</dd>
            </div>
            <div class="flex justify-between border-t pt-2 font-semibold">
              <dt>Total</dt>
              <dd>{{ formatPrice(booking.total_price) }}</dd>
            </div>
          </dl>

          <!-- Add-ons list -->
          <div v-if="booking.add_ons?.length > 0" class="mt-4">
            <p
              class="mb-2 text-xs font-medium text-gray-500 uppercase tracking-wide"
            >
              Selected Add-ons
            </p>
            <ul class="space-y-1 text-sm text-gray-700">
              <li v-for="addon in booking.add_ons" :key="addon.id">
                • {{ addon.name }} —
                {{ formatPrice(addon.pivot?.price ?? addon.price) }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Cancel button -->
      <div v-if="canCancel" class="mt-6">
        <button
          :disabled="cancelLoading"
          class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60"
          @click="cancelBooking"
        >
          {{ cancelLoading ? "Cancelling…" : "Cancel Booking" }}
        </button>
      </div>

      <!-- Review form -->
      <div
        v-if="canReview"
        id="review"
        class="mt-8 rounded-xl border bg-white p-6 shadow-sm"
      >
        <h2 class="mb-4 flex items-center gap-2 font-semibold text-gray-900">
          <StarIcon class="h-5 w-5 text-yellow-400" />
          Leave a Review
        </h2>

        <div
          v-if="reviewSuccess"
          class="rounded-lg bg-green-50 p-4 text-green-700"
        >
          Thank you for your review!
        </div>

        <form v-else @submit.prevent="submitReview" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700"
              >Rating</label
            >
            <div class="mt-1 flex gap-2">
              <button
                v-for="n in 5"
                :key="n"
                type="button"
                class="text-2xl"
                :class="
                  n <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-300'
                "
                @click="reviewForm.rating = n"
              >
                ★
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700"
              >Comment (optional)</label
            >
            <textarea
              v-model="reviewForm.comment"
              rows="3"
              class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
              placeholder="Share your experience…"
            ></textarea>
          </div>
          <button
            type="submit"
            :disabled="reviewLoading"
            class="rounded-lg bg-yellow-500 px-5 py-2 text-sm font-semibold text-white hover:bg-yellow-600 disabled:opacity-60"
          >
            {{ reviewLoading ? "Submitting…" : "Submit Review" }}
          </button>
        </form>
      </div>

      <!-- Existing review -->
      <div
        v-else-if="booking.review"
        class="mt-8 rounded-xl border bg-white p-6 shadow-sm"
      >
        <h2 class="mb-3 flex items-center gap-2 font-semibold text-gray-900">
          <StarIcon class="h-5 w-5 text-yellow-400" />
          Your Review
        </h2>
        <div class="text-yellow-400">
          {{ "★".repeat(booking.review.rating)
          }}{{ "☆".repeat(5 - booking.review.rating) }}
        </div>
        <p v-if="booking.review.comment" class="mt-2 text-sm text-gray-600">
          {{ booking.review.comment }}
        </p>
      </div>
    </template>
  </div>
</template>
