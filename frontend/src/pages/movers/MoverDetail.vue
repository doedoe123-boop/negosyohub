<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  TruckIcon,
  MapPinIcon,
  PhoneIcon,
  CheckCircleIcon,
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
  return (
    "₱" + (centavos / 100).toLocaleString("en-PH", { minimumFractionDigits: 2 })
  );
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
    mover.value = res.data;
  } catch {
    router.push({ name: "movers.index" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div v-if="loading" class="flex items-center justify-center py-32">
      <div
        class="h-10 w-10 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
      ></div>
    </div>

    <template v-else-if="mover">
      <!-- Hero -->
      <div class="bg-blue-600 py-10 text-white">
        <div class="mx-auto max-w-4xl px-4">
          <div class="flex items-center gap-4">
            <div
              class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20"
            >
              <TruckIcon class="h-8 w-8" />
            </div>
            <div>
              <h1 class="text-2xl font-bold">{{ mover.name }}</h1>
              <p class="mt-1 flex items-center gap-1 text-blue-100">
                <MapPinIcon class="h-4 w-4" />
                {{ mover.city
                }}<span v-if="mover.province">, {{ mover.province }}</span>
              </p>
            </div>
          </div>
          <p v-if="mover.description" class="mt-4 text-blue-100">
            {{ mover.description }}
          </p>
        </div>
      </div>

      <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
          <!-- Add-ons -->
          <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">
              Available Services
            </h2>
            <div v-if="activeAddOns.length === 0" class="text-sm text-gray-500">
              No add-ons listed. Standard moving service included.
            </div>
            <ul v-else class="space-y-3">
              <li
                v-for="addon in activeAddOns"
                :key="addon.id"
                class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                :class="
                  selectedAddOns.includes(addon.id)
                    ? 'border-blue-500 bg-blue-50'
                    : 'hover:border-gray-300'
                "
                @click="toggleAddOn(addon.id)"
              >
                <CheckCircleIcon
                  class="mt-0.5 h-5 w-5 shrink-0"
                  :class="
                    selectedAddOns.includes(addon.id)
                      ? 'text-blue-600'
                      : 'text-gray-300'
                  "
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900">{{ addon.name }}</p>
                  <p v-if="addon.description" class="text-sm text-gray-500">
                    {{ addon.description }}
                  </p>
                </div>
                <span class="text-sm font-semibold text-gray-700 shrink-0">{{
                  formatPrice(addon.price)
                }}</span>
              </li>
            </ul>
            <div
              v-if="selectedAddOns.length > 0"
              class="mt-4 text-right text-sm font-medium text-blue-600"
            >
              Add-ons: {{ formatPrice(addOnTotal) }}
            </div>
          </div>

          <!-- Booking form -->
          <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">
              Book a Move
            </h2>

            <div
              v-if="bookingSuccess"
              class="rounded-lg bg-green-50 p-4 text-green-700"
            >
              <p class="font-medium">Booking submitted!</p>
              <p class="mt-1 text-sm">
                The moving company will confirm your booking soon.
              </p>
              <RouterLink
                :to="{ name: 'account.moving' }"
                class="mt-2 inline-block text-sm font-medium underline"
              >
                View My Bookings →
              </RouterLink>
            </div>

            <form v-else @submit.prevent="submitBooking" class="space-y-4">
              <div
                v-if="bookingError"
                class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
              >
                {{ bookingError }}
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Contact Name</label
                >
                <input
                  v-model="form.contact_name"
                  required
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Contact Phone</label
                >
                <input
                  v-model="form.contact_phone"
                  required
                  type="tel"
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Pickup Address</label
                >
                <input
                  v-model="form.pickup_address"
                  required
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Pickup City</label
                >
                <input
                  v-model="form.pickup_city"
                  required
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Delivery Address</label
                >
                <input
                  v-model="form.delivery_address"
                  required
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Delivery City</label
                >
                <input
                  v-model="form.delivery_city"
                  required
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Scheduled Date & Time</label
                >
                <input
                  v-model="form.scheduled_at"
                  required
                  type="datetime-local"
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Notes (optional)</label
                >
                <textarea
                  v-model="form.notes"
                  rows="2"
                  class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                ></textarea>
              </div>
              <button
                type="submit"
                :disabled="bookingLoading"
                class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
              >
                <span v-if="bookingLoading">Submitting…</span>
                <span v-else>Request Moving Service</span>
              </button>
              <p
                v-if="!auth.isLoggedIn"
                class="text-center text-xs text-gray-500"
              >
                You'll be asked to log in first.
              </p>
            </form>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
