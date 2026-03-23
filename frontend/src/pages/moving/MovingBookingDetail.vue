<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  TruckIcon,
  MapPinIcon,
  CalendarIcon,
  StarIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  ArrowPathIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarIconSolid } from "@heroicons/vue/24/solid";
import { movingBookingsApi } from "@/api/movingBookings";

const route = useRoute();
const router = useRouter();

const booking = ref(null);
const loading = ref(true);
const cancelLoading = ref(false);
const reviewLoading = ref(false);
const reviewSuccess = ref(false);

const reviewForm = ref({ rating: 5, comment: "" });

const statusConfig = {
  pending: {
    label: "Pending Review",
    icon: ClockIcon,
    colorClass: "bg-amber-50 text-amber-700 ring-amber-100",
    dotClass: "bg-amber-400",
  },
  confirmed: {
    label: "Confirmed",
    icon: CheckCircleIcon,
    colorClass: "bg-blue-50 text-blue-700 ring-blue-100",
    dotClass: "bg-blue-400",
  },
  in_progress: {
    label: "In Progress",
    icon: ArrowPathIcon,
    colorClass: "bg-indigo-50 text-indigo-700 ring-indigo-100",
    dotClass: "bg-indigo-400",
  },
  completed: {
    label: "Completed",
    icon: CheckCircleIcon,
    colorClass: "bg-emerald-50 text-emerald-700 ring-emerald-100",
    dotClass: "bg-emerald-400",
  },
  cancelled: {
    label: "Cancelled",
    icon: XCircleIcon,
    colorClass: "bg-rose-50 text-rose-700 ring-rose-100",
    dotClass: "bg-rose-400",
  },
};

const canCancel = computed(() => booking.value?.status === "pending");

const canReview = computed(
  () => booking.value?.status === "completed" && !booking.value?.review,
);

function formatDate(dt) {
  if (!dt) return "—";
  return new Date(dt).toLocaleString("en-PH", {
    month: "long",
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
    currency: "PHP",
  });
}

async function cancelBooking() {
  if (!confirm("Are you sure you want to cancel this booking?")) return;
  cancelLoading.value = true;
  try {
    await movingBookingsApi.cancel(booking.value.id);
    booking.value.status = "cancelled";
  } catch (err) {
    alert("Failed to cancel booking. Please try again.");
  } finally {
    cancelLoading.value = false;
  }
}

async function submitReview() {
  reviewLoading.value = true;
  try {
    await movingBookingsApi.submitReview(booking.value.id, reviewForm.value);
    reviewSuccess.value = true;
    booking.value.review = { ...reviewForm.value };
  } catch (err) {
    alert("Failed to submit review.");
  } finally {
    reviewLoading.value = false;
  }
}

onMounted(async () => {
  try {
    const res = await movingBookingsApi.show(route.params.id);
    booking.value = res.data.data ?? res.data;
  } catch {
    router.push({ name: "account.moving" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="theme-page mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Back Button -->
    <button
      @click="router.push({ name: 'account.moving' })"
      class="theme-copy group mb-6 inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:text-brand-600"
    >
      <ArrowLeftIcon
        class="size-4 transition-transform group-hover:-translate-x-1"
      />
      Back to My Bookings
    </button>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="space-y-6">
      <div
        class="h-20 animate-pulse rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700"
        
      />
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div
          class="theme-card lg:col-span-2 h-96 animate-pulse rounded-3xl"
        />
        <div
          class="theme-card h-96 animate-pulse rounded-3xl"
        />
      </div>
    </div>

    <!-- Main Content -->
    <template v-else-if="booking">
      <!-- Top Title Bar -->
      <div
        class="theme-card mb-8 flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h1 class="theme-title text-2xl font-extrabold tracking-tight">
            Booking <span class="text-brand-600">#{{ booking.id }}</span>
          </h1>
          <p class="theme-copy mt-1 text-sm">
            Created on {{ new Date(booking.created_at).toLocaleDateString() }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-wider ring-1 shadow-sm"
            :class="
              statusConfig[booking.status]?.colorClass ??
              'bg-slate-50 text-slate-700 ring-slate-200'
            "
          >
            <span
              class="size-2 rounded-full"
              :class="statusConfig[booking.status]?.dotClass ?? 'bg-slate-400'"
            ></span>
            {{ statusConfig[booking.status]?.label ?? booking.status }}
          </span>
          <button
            v-if="canCancel"
            :disabled="cancelLoading"
            class="rounded-xl border border-rose-200 bg-white px-4 py-1.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-50"
            @click="cancelBooking"
          >
            {{ cancelLoading ? "Cancelling..." : "Cancel" }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Move Details Column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Main Details Card -->
          <div
            class="theme-card rounded-3xl p-8"
          >
            <h2
              class="theme-title mb-6 flex items-center gap-2 text-lg font-bold"
            >
              <TruckIcon class="size-5 text-brand-600" />
              Service Information
            </h2>

            <div class="grid gap-6 sm:grid-cols-2">
              <div class="space-y-4">
                <div>
                  <label
                    class="theme-copy text-[10px] font-bold uppercase tracking-widest"
                    >Moving Company</label
                  >
                  <p class="theme-title font-bold">
                    {{ booking.store?.name }}
                  </p>
                </div>
                <div>
                  <label
                    class="theme-copy text-[10px] font-bold uppercase tracking-widest"
                    >Scheduled Date</label
                  >
                  <div class="theme-title flex items-center gap-2 font-bold">
                    <CalendarIcon class="size-4 text-brand-600" />
                    {{ formatDate(booking.scheduled_at) }}
                  </div>
                </div>
              </div>

              <div>
                <label
                  class="theme-copy text-[10px] font-bold uppercase tracking-widest"
                  >Total Estimate</label
                >
                <p class="text-2xl font-black text-brand-600">
                  {{ formatPrice(booking.total_price) }}
                </p>
                <p class="theme-copy mt-1 text-[10px] italic">
                  Includes base price and selected add-ons
                </p>
              </div>
            </div>

            <div
              class="theme-divider-soft mt-8 grid gap-8 border-t pt-8 sm:grid-cols-2"
            >
              <div class="space-y-4">
                <div class="flex items-start gap-4">
                  <div
                    class="theme-card-muted flex size-10 shrink-0 items-center justify-center rounded-xl text-brand-600"
                  >
                    <MapPinIcon class="size-5" />
                  </div>
                  <div>
                    <label
                      class="theme-copy text-[10px] font-bold uppercase tracking-widest"
                      >Pickup Location</label
                    >
                    <p class="theme-title text-sm font-semibold">
                      {{ booking.pickup_address }}
                    </p>
                    <p class="theme-copy text-xs">
                      {{ booking.pickup_city }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-4">
                  <div
                    class="theme-card-muted flex size-10 shrink-0 items-center justify-center rounded-xl text-indigo-600"
                  >
                    <MapPinIcon class="size-5" />
                  </div>
                  <div>
                    <label
                      class="theme-copy text-[10px] font-bold uppercase tracking-widest"
                      >Delivery Destination</label
                    >
                    <p class="theme-title text-sm font-semibold">
                      {{ booking.delivery_address }}
                    </p>
                    <p class="theme-copy text-xs">
                      {{ booking.delivery_city }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div
              v-if="booking.notes"
              class="mt-8 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100"
            >
              <h3
                class="flex items-center gap-2 text-xs font-bold text-amber-900 uppercase tracking-wide"
              >
                <DocumentTextIcon class="size-3.5" />
                Notes to Mover
              </h3>
              <p class="mt-2 text-sm text-amber-700 leading-relaxed">
                {{ booking.notes }}
              </p>
            </div>
          </div>

          <!-- Reviews Integrated Card -->
          <div
            v-if="canReview || booking.review"
            class="theme-card rounded-3xl p-8"
          >
            <h2
              class="theme-title mb-6 flex items-center gap-2 text-lg font-bold"
            >
              <StarIcon class="size-5 text-amber-400" />
              Service Feedback
            </h2>

            <div
              v-if="reviewSuccess || booking.review"
              class="theme-card-muted rounded-2xl p-6"
            >
              <div class="flex items-center gap-1 mb-3">
                <StarIconSolid
                  v-for="i in 5"
                  :key="i"
                  class="size-5"
                  :class="
                    i <= (booking.review?.rating || 0)
                      ? 'text-amber-400'
                      : 'text-slate-200'
                  "
                />
                <span class="theme-title ml-2 text-sm font-bold"
                  >{{ booking.review?.rating }}/5</span
                >
              </div>
              <p class="theme-copy text-sm italic">
                "{{ booking.review?.comment || "No comment provided." }}"
              </p>
              <div
                v-if="reviewSuccess"
                class="mt-4 flex items-center gap-2 text-xs font-bold text-emerald-600"
              >
                <CheckCircleIcon class="size-4" />
                Review submitted successfully!
              </div>
            </div>

            <form v-else @submit.prevent="submitReview" class="space-y-6">
              <p class="theme-copy text-sm">
                How would you rate your experience with
                {{ booking.store?.name }}?
              </p>
              <div>
                <div class="flex items-center gap-1">
                  <button
                    v-for="n in 5"
                    :key="n"
                    type="button"
                    class="transition-transform hover:scale-110 active:scale-95 outline-none"
                    @click="reviewForm.rating = n"
                  >
                    <StarIconSolid
                      class="size-8"
                      :class="
                        n <= reviewForm.rating
                          ? 'text-amber-400'
                          : 'text-slate-200'
                      "
                    />
                  </button>
                </div>
              </div>
              <div>
                <label
                  class="theme-copy mb-2 block text-xs font-bold uppercase tracking-widest"
                  >Detailed Feedback</label
                >
                <textarea
                  v-model="reviewForm.comment"
                  rows="3"
                  class="theme-input w-full rounded-2xl py-3 px-4 text-sm shadow-inner transition-colors focus:border-brand-500 focus:bg-white focus:ring-brand-500"
                  placeholder="Tell others about the service, punctuality, and care of items..."
                ></textarea>
              </div>
              <button
                type="submit"
                :disabled="reviewLoading"
                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-brand-700 disabled:opacity-50 sm:w-auto"
              >
                <span v-if="reviewLoading" class="animate-spin truncate"
                  >...</span
                >
                <span v-else>Submit Review</span>
                <ChevronRightIcon class="size-4" />
              </button>
            </form>
          </div>
        </div>

        <!-- Summary Column -->
        <div class="space-y-6">
          <!-- Final Bill Card -->
          <div
            class="theme-card sticky top-8 rounded-3xl p-6 shadow-xl"
          >
            <h2
              class="theme-copy mb-6 flex items-center gap-2 text-sm font-bold uppercase tracking-widest"
            >
              <CurrencyDollarIcon class="size-4 text-brand-400" />
              Pricing Breakdown
            </h2>

            <div class="space-y-4">
              <div class="flex justify-between text-sm">
                <span class="theme-copy">Base Moving Rate</span>
                <span class="theme-title font-bold font-mono">{{
                  formatPrice(booking.base_price)
                }}</span>
              </div>

              <div
                v-if="booking.add_ons_total > 0"
                class="flex justify-between text-sm"
              >
                <span class="theme-copy">Service Add-ons</span>
                <span class="theme-title font-bold font-mono"
                  >+{{ formatPrice(booking.add_ons_total) }}</span
                >
              </div>

              <div class="theme-divider-soft mt-4 border-t pt-4">
                <div class="flex items-end justify-between">
                  <span
                    class="text-xs font-bold uppercase tracking-widest text-brand-400"
                    >Total Due</span
                  >
                  <span
                    class="theme-title text-2xl font-black font-mono leading-none tracking-tight"
                    >{{ formatPrice(booking.total_price) }}</span
                  >
                </div>
              </div>
            </div>

            <!-- Selected Add-ons Pill List -->
            <div v-if="booking.add_ons?.length > 0" class="mt-8 space-y-3">
              <p
                class="theme-copy text-[10px] font-bold uppercase tracking-widest"
              >
                Selected Services
              </p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="addon in booking.add_ons"
                  :key="addon.id"
                  class="theme-badge-neutral inline-flex items-center rounded-lg px-2 py-1 text-[10px] font-medium"
                >
                  {{ addon.name }}
                </span>
              </div>
            </div>
          </div>

          <!-- Helper Box -->
          <div
            class="theme-card-muted rounded-3xl p-6 shadow-sm"
          >
            <h3 class="theme-title text-sm font-bold">
              Need help with your move?
            </h3>
            <p class="theme-copy mt-2 text-xs leading-relaxed">
              Contact the mover directly through the platform or our support
              team if you experience any issues.
            </p>
            <button
              class="mt-4 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
            >
              Contact Support →
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.font-mono {
  font-family: "Space Mono", "JetBrains Mono", monospace;
}
</style>
