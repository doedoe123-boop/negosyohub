<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  TruckIcon,
  MapPinIcon,
  PhoneIcon,
  CheckCircleIcon,
  ArrowLeftIcon,
  CalendarDaysIcon,
  UserIcon,
  DocumentTextIcon,
  StarIcon,
  CurrencyDollarIcon,
  CheckBadgeIcon
} from "@heroicons/vue/24/outline";
import { moversApi } from "@/api/movers";
import { movingBookingsApi } from "@/api/movingBookings";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const mover = ref(null);
const loading = ref(true);
const bookingLoading = ref(false);
const bookingSuccess = ref(false);
const bookingError = ref("");

const selectedAddOns = ref([]);

const form = ref({
  pickup_address: "",
  delivery_address: "",
  pickup_city: "",
  delivery_city: "",
  scheduled_at: "",
  contact_name: auth.user?.name ?? "",
  contact_phone: "",
  notes: "",
  rental_agreement_id: route.query.rental_id
    ? Number(route.query.rental_id)
    : null,
});

const activeAddOns = computed(() => mover.value?.moving_add_ons ?? []);

const addOnTotal = computed(() =>
  selectedAddOns.value.reduce((sum, id) => {
    const addon = activeAddOns.value.find((a) => a.id === id);
    return sum + (addon?.price ?? 0);
  }, 0),
);

function toggleAddOn(id) {
  const idx = selectedAddOns.value.indexOf(id);
  if (idx === -1) {
    selectedAddOns.value.push(id);
  } else {
    selectedAddOns.value.splice(idx, 1);
  }
}

function formatPrice(centavos) {
  if (centavos == null) return "—";
  return (centavos / 100).toLocaleString("en-PH", {
    style: "currency",
    currency: "PHP"
  });
}

async function submitBooking() {
  if (!auth.isLoggedIn) {
    router.push({ name: "auth.login", query: { redirect: route.fullPath } });
    return;
  }

  bookingLoading.value = true;
  bookingError.value = "";

  try {
    const payload = {
      store_id: mover.value.id,
      ...form.value,
      add_on_ids: selectedAddOns.value,
    };
    if (!payload.rental_agreement_id) delete payload.rental_agreement_id;

    await movingBookingsApi.create(payload);
    bookingSuccess.value = true;
  } catch (err) {
    bookingError.value =
      err.response?.data?.message ??
      "Failed to create booking. Please try again.";
  } finally {
    bookingLoading.value = false;
  }
}

onMounted(async () => {
  try {
    const res = await moversApi.show(route.params.slug);
    mover.value = res.data.data ?? res.data;
  } catch {
    router.push({ name: "movers.index" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div v-if="loading" class="flex items-center justify-center py-32">
       <div class="flex flex-col items-center gap-4">
          <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand-600 border-t-transparent shadow-sm"></div>
          <p class="text-sm font-medium text-slate-500 animate-pulse">Loading mover details...</p>
       </div>
    </div>

    <template v-else-if="mover">
      <!-- Premium Hero Section -->
      <div class="relative overflow-hidden bg-slate-900 py-12 text-white">
        <!-- Abstract background pattern -->
        <div class="absolute inset-0 opacity-10">
          <svg class="h-full w-full" fill="none" viewBox="0 0 100 100">
            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
              <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>
        </div>
        
        <div class="relative mx-auto max-w-5xl px-4 sm:px-6">
          <button
            @click="router.push({ name: 'movers.index' })"
            class="group mb-8 inline-flex items-center gap-2 text-sm font-medium text-slate-400 transition-colors hover:text-white"
          >
            <ArrowLeftIcon class="size-4 transition-transform group-hover:-translate-x-1" />
            Back to All Movers
          </button>

          <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-brand-600/20 text-brand-400 ring-1 ring-brand-400/30 backdrop-blur-sm">
              <TruckIcon class="size-10" />
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl text-white">
                  {{ mover.name }}
                </h1>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-400 ring-1 ring-emerald-500/20">
                  <CheckBadgeIcon class="size-3" />
                  Verified Provider
                </span>
              </div>
              <p class="mt-2 flex items-center gap-1.5 text-slate-400 text-sm font-medium">
                <MapPinIcon class="size-4 text-brand-500" />
                {{ mover.city }}<span v-if="mover.province">, {{ mover.province }}</span>
              </p>
            </div>
          </div>
          
          <div v-if="mover.description" class="mt-6 max-w-2xl text-slate-400 text-lg leading-relaxed">
            {{ mover.description }}
          </div>
        </div>
      </div>

      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
          
          <!-- Services Sidebar / List -->
          <div class="lg:col-span-5 space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
               <div class="mb-6">
                  <h2 class="text-lg font-bold text-slate-900">Custom Services</h2>
                  <p class="text-xs text-slate-500">Pick the services you need for your move.</p>
               </div>

              <div v-if="activeAddOns.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-slate-400">
                <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-slate-50">
                  <DocumentTextIcon class="size-6 text-slate-300" />
                </div>
                <p class="text-sm">Standard moving service included.</p>
              </div>
              
              <div v-else class="space-y-3">
                <div
                  v-for="addon in activeAddOns"
                  :key="addon.id"
                  @click="toggleAddOn(addon.id)"
                  class="group flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-all"
                  :class="selectedAddOns.includes(addon.id)
                      ? 'border-brand-500 bg-brand-50 shadow-sm ring-1 ring-brand-500/20'
                      : 'border-slate-100 hover:border-slate-300 hover:bg-slate-50'"
                >
                  <div class="mt-0.5 relative">
                    <div class="size-5 rounded-full border-2 transition-colors" 
                      :class="selectedAddOns.includes(addon.id) ? 'border-brand-600 bg-brand-600' : 'border-slate-300 bg-white group-hover:border-slate-400'"
                    ></div>
                    <CheckCircleIcon v-if="selectedAddOns.includes(addon.id)" class="absolute inset-0 size-5 text-white" />
                  </div>
                  
                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-900 transition-colors" :class="selectedAddOns.includes(addon.id) ? 'text-brand-700' : ''">
                      {{ addon.name }}
                    </p>
                    <p v-if="addon.description" class="mt-0.5 text-xs text-slate-500 leading-normal">{{ addon.description }}</p>
                  </div>
                  
                  <span class="text-sm font-black text-slate-700 tabular-nums">{{ formatPrice(addon.price) }}</span>
                </div>
              </div>

              <!-- Price Summary Mini-Card -->
              <div v-if="selectedAddOns.length > 0" class="mt-6 rounded-2xl bg-slate-900 p-4 text-white shadow-lg shadow-brand-900/10">
                 <div class="flex items-center justify-between">
                   <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Add-ons Total</span>
                   <span class="text-lg font-black font-mono">{{ formatPrice(addOnTotal) }}</span>
                 </div>
              </div>
            </div>

            <!-- Trust Badge -->
            <div class="rounded-3xl bg-indigo-50 p-6 border border-indigo-100">
              <h3 class="flex items-center gap-2 text-sm font-bold text-indigo-900">
                <StarIcon class="size-4 text-indigo-600" />
                Trusted Professional
              </h3>
              <p class="mt-2 text-xs text-indigo-700 leading-relaxed">
                All providers on NegosyoHub are vetted for reliability and high service standards.
              </p>
            </div>
          </div>

          <!-- Booking Form Area -->
          <div class="lg:col-span-7">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
              <div class="mb-8 border-b border-slate-100 pb-6">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Book Moving Service</h2>
                <p class="mt-1 text-sm text-slate-500">Provide your move details to receive a final confirmation.</p>
              </div>

              <div
                v-if="bookingSuccess"
                class="flex flex-col items-center justify-center py-12 text-center"
              >
                <div class="relative mb-6">
                  <div class="absolute -inset-4 rounded-full bg-emerald-50 opacity-50 blur-xl animate-pulse"></div>
                  <div class="relative flex size-20 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600 shadow-inner ring-1 ring-emerald-200">
                    <CheckCircleIcon class="size-12" />
                  </div>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Request Sent!</h3>
                <p class="mt-2 max-w-sm text-slate-500">
                  {{ mover.name }} will review your details and confirm the schedule shortly.
                </p>
                <div class="mt-10 flex flex-wrap justify-center gap-4">
                  <RouterLink
                    :to="{ name: 'account.moving' }"
                    class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-200 transition-all hover:bg-brand-700 active:scale-95"
                  >
                    Manage My Bookings
                  </RouterLink>
                  <button @click="bookingSuccess = false; form = { ...form }" class="text-sm font-bold text-slate-500 hover:text-slate-900 underline underline-offset-4">
                    Book another move
                  </button>
                </div>
              </div>

              <form v-else @submit.prevent="submitBooking" class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div v-if="bookingError" class="col-span-full rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-600 ring-1 ring-rose-100">
                  {{ bookingError }}
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Contact Name</label>
                  <div class="relative">
                    <UserIcon class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input v-model="form.contact_name" required class="w-full rounded-2xl border-slate-200 bg-slate-50 pl-10 pr-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Phone Number</label>
                  <div class="relative">
                    <PhoneIcon class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input v-model="form.contact_phone" required type="tel" placeholder="09XX XXX XXXX" class="w-full rounded-2xl border-slate-200 bg-slate-50 pl-10 pr-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" />
                  </div>
                </div>

                <div class="col-span-full pt-4 font-bold text-slate-900 text-sm">Location Details</div>

                <div class="space-y-4">
                  <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Pickup Address</label>
                    <input v-model="form.pickup_address" required class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="Bldg/Street/Barangay" />
                  </div>
                  <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Pickup City</label>
                    <input v-model="form.pickup_city" required class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="E.g. Makati City" />
                  </div>
                </div>

                <div class="space-y-4">
                  <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Delivery Address</label>
                    <input v-model="form.delivery_address" required class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="Bldg/Street/Barangay" />
                  </div>
                  <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Delivery City</label>
                    <input v-model="form.delivery_city" required class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="E.g. Quezon City" />
                  </div>
                </div>

                <div class="col-span-full">
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Preferred Schedule</label>
                  <div class="relative">
                    <CalendarDaysIcon class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input v-model="form.scheduled_at" required type="datetime-local" class="w-full rounded-2xl border-slate-200 bg-slate-50 pl-10 pr-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" />
                  </div>
                </div>

                <div class="col-span-full">
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Special Instructions</label>
                  <textarea v-model="form.notes" rows="3" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-inner focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all" placeholder="Any fragile items? Heavy furniture? Elevator access?"></textarea>
                </div>

                <div class="col-span-full pt-4">
                  <button
                    type="submit"
                    :disabled="bookingLoading"
                    class="group relative w-full overflow-hidden rounded-2xl bg-brand-600 px-6 py-4 text-base font-black text-white shadow-xl shadow-brand-500/20 transition-all hover:bg-brand-700 hover:shadow-brand-500/30 active:scale-[0.98] disabled:opacity-50"
                  >
                    <span class="flex items-center justify-center gap-2">
                       <TruckIcon class="size-5 transition-transform group-hover:translate-x-1" />
                       {{ bookingLoading ? 'Processing Request...' : 'Confirm Move Request' }}
                    </span>
                  </button>
                  <p v-if="!auth.isLoggedIn" class="mt-4 text-center text-xs text-slate-400 italic">
                    You'll be directed to login before completing your booking.
                  </p>
                </div>
              </form>
            </div>
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
