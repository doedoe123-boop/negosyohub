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
  ChevronRightIcon
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
    dotClass: "bg-amber-400"
  },
  confirmed: {
    label: "Confirmed",
    icon: CheckCircleIcon,
    colorClass: "bg-blue-50 text-blue-700 ring-blue-100",
    dotClass: "bg-blue-400"
  },
  in_progress: {
    label: "In Progress",
    icon: ArrowPathIcon,
    colorClass: "bg-indigo-50 text-indigo-700 ring-indigo-100",
    dotClass: "bg-indigo-400"
  },
  completed: {
    label: "Completed",
    icon: CheckCircleIcon,
    colorClass: "bg-emerald-50 text-emerald-700 ring-emerald-100",
    dotClass: "bg-emerald-400"
  },
  cancelled: {
    label: "Cancelled",
    icon: XCircleIcon,
    colorClass: "bg-rose-50 text-rose-700 ring-rose-100",
    dotClass: "bg-rose-400"
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
    currency: "PHP"
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
  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Back Button -->
    <button
      @click="router.push({ name: 'account.moving' })"
      class="group mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition-colors hover:text-brand-600"
    >
      <ArrowLeftIcon class="size-4 transition-transform group-hover:-translate-x-1" />
      Back to My Bookings
    </button>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="space-y-6">
      <div class="h-20 animate-pulse rounded-3xl bg-white shadow-sm ring-1 ring-slate-200" />
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 h-96 animate-pulse rounded-3xl bg-white shadow-sm ring-1 ring-slate-200" />
        <div class="h-96 animate-pulse rounded-3xl bg-white shadow-sm ring-1 ring-slate-200" />
      </div>
    </div>

    <!-- Main Content -->
    <template v-else-if="booking">
      <!-- Top Title Bar -->
      <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-6 rounded-3xl shadow-sm ring-1 ring-slate-200">
        <div>
          <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
            Booking <span class="text-brand-600">#{{ booking.id }}</span>
          </h1>
          <p class="mt-1 text-sm text-slate-500">
            Created on {{ new Date(booking.created_at).toLocaleDateString() }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-wider ring-1 shadow-sm"
            :class="statusConfig[booking.status]?.colorClass ?? 'bg-slate-50 text-slate-700 ring-slate-200'"
          >
            <span class="size-2 rounded-full" :class="statusConfig[booking.status]?.dotClass ?? 'bg-slate-400'"></span>
            {{ statusConfig[booking.status]?.label ?? booking.status }}
          </span>
          <button
            v-if="canCancel"
            :disabled="cancelLoading"
            class="rounded-xl border border-rose-200 bg-white px-4 py-1.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-50"
            @click="cancelBooking"
          >
            {{ cancelLoading ? 'Cancelling...' : 'Cancel' }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Move Details Column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Main Details Card -->
          <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <h2 class="mb-6 flex items-center gap-2 text-lg font-bold text-slate-900">
              <TruckIcon class="size-5 text-brand-600" />
              Service Information
            </h2>

            <div class="grid gap-6 sm:grid-cols-2">
              <div class="space-y-4">
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Moving Company</label>
                  <p class="font-bold text-slate-900">{{ booking.store?.name }}</p>
                </div>
                <div>
                  <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Scheduled Date</label>
                  <div class="flex items-center gap-2 font-bold text-slate-900">
                    <CalendarIcon class="size-4 text-brand-600" />
                    {{ formatDate(booking.scheduled_at) }}
                  </div>
                </div>
              </div>
              
              <div>
                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Estimate</label>
                <p class="text-2xl font-black text-brand-600">{{ formatPrice(booking.total_price) }}</p>
                <p class="text-[10px] text-slate-400 italic mt-1">Includes base price and selected add-ons</p>
              </div>
            </div>

            <div class="mt-8 pt-8 border-t border-slate-100 grid gap-8 sm:grid-cols-2">
              <div class="space-y-4">
                <div class="flex items-start gap-4">
                  <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-brand-600 ring-1 ring-slate-100">
                    <MapPinIcon class="size-5" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pickup Location</label>
                    <p class="text-sm font-semibold text-slate-800">{{ booking.pickup_address }}</p>
                    <p class="text-xs text-slate-500">{{ booking.pickup_city }}</p>
                  </div>
                </div>
              </div>
              
              <div class="space-y-4">
                <div class="flex items-start gap-4">
                  <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-indigo-600 ring-1 ring-slate-100">
                    <MapPinIcon class="size-5" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Delivery Destination</label>
                    <p class="text-sm font-semibold text-slate-800">{{ booking.delivery_address }}</p>
                    <p class="text-xs text-slate-500">{{ booking.delivery_city }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="booking.notes" class="mt-8 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
              <h3 class="flex items-center gap-2 text-xs font-bold text-amber-900 uppercase tracking-wide">
                <DocumentTextIcon class="size-3.5" />
                Notes to Mover
              </h3>
              <p class="mt-2 text-sm text-amber-700 leading-relaxed">{{ booking.notes }}</p>
            </div>
          </div>

          <!-- Reviews Integrated Card -->
          <div v-if="canReview || booking.review" class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
             <h2 class="mb-6 flex items-center gap-2 text-lg font-bold text-slate-900">
              <StarIcon class="size-5 text-amber-400" />
              Service Feedback
            </h2>

            <div v-if="reviewSuccess || booking.review" class="rounded-2xl bg-slate-50 p-6 border border-slate-100">
               <div class="flex items-center gap-1 mb-3">
                 <StarIconSolid 
                  v-for="i in 5" 
                  :key="i" 
                  class="size-5"
                  :class="i <= (booking.review?.rating || 0) ? 'text-amber-400' : 'text-slate-200'"
                 />
                 <span class="ml-2 text-sm font-bold text-slate-900">{{ booking.review?.rating }}/5</span>
               </div>
               <p class="text-sm text-slate-600 italic">"{{ booking.review?.comment || 'No comment provided.' }}"</p>
               <div v-if="reviewSuccess" class="mt-4 flex items-center gap-2 text-xs font-bold text-emerald-600">
                 <CheckCircleIcon class="size-4" />
                 Review submitted successfully!
               </div>
            </div>

            <form v-else @submit.prevent="submitReview" class="space-y-6">
              <p class="text-sm text-slate-500">How would you rate your experience with {{ booking.store?.name }}?</p>
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
                      :class="n <= reviewForm.rating ? 'text-amber-400' : 'text-slate-200'"
                    />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Detailed Feedback</label>
                <textarea
                  v-model="reviewForm.comment"
                  rows="3"
                  class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3 px-4 text-sm shadow-inner transition-colors focus:border-brand-500 focus:bg-white focus:ring-brand-500"
                  placeholder="Tell others about the service, punctuality, and care of items..."
                ></textarea>
              </div>
              <button
                type="submit"
                :disabled="reviewLoading"
                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-brand-700 disabled:opacity-50 sm:w-auto"
              >
                <span v-if="reviewLoading" class="animate-spin truncate">...</span>
                <span v-else>Submit Review</span>
                <ChevronRightIcon class="size-4" />
              </button>
            </form>
          </div>
        </div>

        <!-- Summary Column -->
        <div class="space-y-6">
           <!-- Final Bill Card -->
           <div class="sticky top-8 rounded-3xl bg-slate-900 p-6 text-white shadow-xl ring-1 ring-slate-800">
             <h2 class="mb-6 flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-slate-400">
               <CurrencyDollarIcon class="size-4 text-brand-400" />
               Pricing Breakdown
             </h2>

             <div class="space-y-4">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-400">Base Moving Rate</span>
                  <span class="font-bold font-mono">{{ formatPrice(booking.base_price) }}</span>
                </div>
                
                <div v-if="booking.add_ons_total > 0" class="flex justify-between text-sm">
                  <span class="text-slate-400">Service Add-ons</span>
                  <span class="font-bold font-mono">+{{ formatPrice(booking.add_ons_total) }}</span>
                </div>

                <div class="border-t border-slate-700/50 pt-4 mt-4">
                   <div class="flex items-end justify-between">
                     <span class="text-xs font-bold uppercase tracking-widest text-brand-400">Total Due</span>
                     <span class="text-2xl font-black font-mono leading-none tracking-tight">{{ formatPrice(booking.total_price) }}</span>
                   </div>
                </div>
             </div>

             <!-- Selected Add-ons Pill List -->
             <div v-if="booking.add_ons?.length > 0" class="mt-8 space-y-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Selected Services</p>
                <div class="flex flex-wrap gap-2">
                   <span 
                    v-for="addon in booking.add_ons" 
                    :key="addon.id"
                    class="inline-flex items-center rounded-lg bg-slate-800 px-2 py-1 text-[10px] font-medium text-slate-300 ring-1 ring-slate-700"
                   >
                     {{ addon.name }}
                   </span>
                </div>
             </div>
           </div>

           <!-- Helper Box -->
           <div class="rounded-3xl bg-indigo-50 p-6 border border-indigo-100 shadow-sm ring-1 ring-indigo-200">
             <h3 class="text-sm font-bold text-indigo-900">Need help with your move?</h3>
             <p class="mt-2 text-xs text-indigo-700 leading-relaxed">
               Contact the mover directly through the platform or our support team if you experience any issues.
             </p>
             <button class="mt-4 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
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
  font-family: 'Space Mono', 'JetBrains Mono', monospace;
}
</style>
