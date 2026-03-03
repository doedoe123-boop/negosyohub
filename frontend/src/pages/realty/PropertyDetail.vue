<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import {
  ChevronRightIcon,
  MapPinIcon,
  HomeModernIcon,
  ArrowLeftIcon,
  EyeIcon,
  PhoneIcon,
  UserCircleIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
} from "@heroicons/vue/24/outline";
import { propertiesApi } from "@/api/properties";

const route = useRoute();
const property = ref(null);
const loading = ref(true);
const error = ref(null);
const selectedImage = ref(0);

// Inquiry form state
const inquiry = ref({ name: "", email: "", phone: "", message: "" });
const inquirySubmitting = ref(false);
const inquirySuccess = ref(false);
const inquiryError = ref(null);

const listingBadgeClass = {
  for_sale: "bg-emerald-100 text-emerald-700",
  for_rent: "bg-sky-100 text-sky-700",
  for_lease: "bg-amber-100 text-amber-700",
  pre_selling: "bg-purple-100 text-purple-700",
};

const listingLabel = {
  for_sale: "For Sale",
  for_rent: "For Rent",
  for_lease: "For Lease",
  pre_selling: "Pre-Selling",
};

const typeLabel = {
  house: "House & Lot",
  condo: "Condominium",
  apartment: "Apartment",
  townhouse: "Townhouse",
  commercial: "Commercial Space",
  lot: "Vacant Lot",
  warehouse: "Warehouse",
  farm: "Farm / Agricultural",
};

onMounted(async () => {
  try {
    const { data } = await propertiesApi.show(route.params.slug);
    property.value = data;
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? "Property not found."
        : "Failed to load property.";
  } finally {
    loading.value = false;
  }
});

const images = computed(() => property.value?.images ?? []);
const hasGallery = computed(() => images.value.length > 0);

const formattedPrice = computed(() => {
  if (!property.value) return "";
  const p = property.value;
  const formatted = parseFloat(p.price).toLocaleString("en-PH", {
    style: "currency",
    currency: p.price_currency || "PHP",
    maximumFractionDigits: 0,
  });
  return p.price_period
    ? `${formatted} / ${p.price_period.replace("_", " ")}`
    : formatted;
});

const fullAddress = computed(() => {
  if (!property.value) return "";
  return [
    property.value.address_line,
    property.value.barangay,
    property.value.city,
    property.value.province,
  ]
    .filter(Boolean)
    .join(", ");
});

async function submitInquiry() {
  inquiryError.value = null;
  inquirySubmitting.value = true;
  try {
    await propertiesApi.submitInquiry(route.params.slug, inquiry.value);
    inquirySuccess.value = true;
    inquiry.value = { name: "", email: "", phone: "", message: "" };
  } catch {
    inquiryError.value = "Failed to send inquiry. Please try again.";
  } finally {
    inquirySubmitting.value = false;
  }
}
</script>

<template>
  <div>
    <!-- Skeleton -->
    <div v-if="loading" class="animate-pulse">
      <div class="aspect-[16/9] w-full bg-slate-200" />
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
          <div class="space-y-4">
            <div class="h-7 w-2/3 rounded bg-slate-200" />
            <div class="h-4 w-1/3 rounded bg-slate-100" />
            <div class="h-10 w-1/3 rounded bg-slate-200" />
            <div class="h-24 rounded-xl bg-slate-100" />
            <div class="h-4 w-full rounded bg-slate-100" />
            <div class="h-4 w-5/6 rounded bg-slate-100" />
          </div>
          <div class="h-96 rounded-2xl bg-slate-100" />
        </div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6"
    >
      <HomeModernIcon class="mx-auto mb-4 size-12 text-slate-300" />
      <p class="text-lg font-medium text-slate-600">{{ error }}</p>
      <RouterLink
        to="/properties"
        class="mt-4 inline-flex items-center gap-1.5 text-sm text-teal-600 hover:text-teal-700"
      >
        <ArrowLeftIcon class="size-4" /> Back to properties
      </RouterLink>
    </div>

    <template v-else-if="property">
      <!-- Gallery hero -->
      <div class="bg-slate-900">
        <!-- Main image -->
        <div class="relative aspect-[16/9] max-h-[540px] overflow-hidden">
          <img
            v-if="hasGallery"
            :src="images[selectedImage]"
            :alt="property.title"
            class="h-full w-full object-cover opacity-95 transition-all duration-300"
          />
          <div
            v-else
            class="flex h-full items-center justify-center bg-gradient-to-br from-teal-900 to-slate-900"
          >
            <HomeModernIcon class="size-24 text-teal-700/50" />
          </div>

          <!-- Back button overlay -->
          <RouterLink
            to="/properties"
            class="absolute left-4 top-4 flex items-center gap-1.5 rounded-full bg-black/40 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-black/60"
          >
            <ArrowLeftIcon class="size-3.5" />
            Properties
          </RouterLink>

          <!-- Listing type badge overlay -->
          <span
            class="absolute right-4 top-4 rounded-full px-3 py-1 text-xs font-semibold shadow"
            :class="listingBadgeClass[property.listing_type] ?? 'bg-slate-100 text-slate-600'"
          >
            {{ listingLabel[property.listing_type] ?? property.listing_type }}
          </span>
        </div>

        <!-- Thumbnail strip -->
        <div
          v-if="images.length > 1"
          class="flex gap-2 overflow-x-auto bg-slate-950 px-4 py-2"
        >
          <button
            v-for="(img, i) in images"
            :key="i"
            class="size-16 shrink-0 overflow-hidden rounded-lg border-2 transition-all"
            :class="
              selectedImage === i
                ? 'border-teal-400 opacity-100'
                : 'border-transparent opacity-50 hover:opacity-80'
            "
            @click="selectedImage = i"
          >
            <img :src="img" class="h-full w-full object-cover" />
          </button>
        </div>
      </div>

      <!-- Price + location bar -->
      <div class="border-b border-teal-700 bg-teal-600 text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
          <div>
            <p class="text-2xl font-bold tracking-tight">{{ formattedPrice }}</p>
            <p v-if="fullAddress" class="mt-0.5 flex items-center gap-1 text-sm text-teal-100">
              <MapPinIcon class="size-4 shrink-0" />
              {{ fullAddress }}
            </p>
          </div>
          <div class="flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm text-teal-100">
            <EyeIcon class="size-4" />
            {{ property.views_count?.toLocaleString() ?? 0 }} views
          </div>
        </div>
      </div>

      <!-- Main layout -->
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-1.5 text-xs text-slate-400" aria-label="Breadcrumb">
          <RouterLink to="/" class="transition-colors hover:text-teal-600">Home</RouterLink>
          <ChevronRightIcon class="size-3" />
          <RouterLink to="/properties" class="transition-colors hover:text-teal-600">Properties</RouterLink>
          <ChevronRightIcon class="size-3" />
          <span class="line-clamp-1 text-slate-600">{{ property.title }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
          <!-- Left: main content -->
          <div>
            <!-- Title + type -->
            <div class="mb-1 flex flex-wrap items-center gap-2">
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                {{ typeLabel[property.property_type] ?? property.property_type }}
              </span>
            </div>
            <h1 class="mb-4 text-2xl font-bold leading-snug text-slate-900 sm:text-3xl">
              {{ property.title }}
            </h1>

            <!-- Specs bar -->
            <div
              v-if="property.bedrooms != null || property.bathrooms != null || property.garage_spaces != null || property.floor_area || property.lot_area"
              class="mb-8 grid grid-cols-2 gap-4 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:grid-cols-3 lg:grid-cols-5"
            >
              <div v-if="property.bedrooms != null" class="flex flex-col items-center gap-1 text-center">
                <span class="text-2xl">🛏</span>
                <span class="text-lg font-bold text-slate-900">{{ property.bedrooms }}</span>
                <span class="text-xs text-slate-500">Bedrooms</span>
              </div>
              <div v-if="property.bathrooms != null" class="flex flex-col items-center gap-1 text-center">
                <span class="text-2xl">🚿</span>
                <span class="text-lg font-bold text-slate-900">{{ property.bathrooms }}</span>
                <span class="text-xs text-slate-500">Bathrooms</span>
              </div>
              <div v-if="property.garage_spaces != null" class="flex flex-col items-center gap-1 text-center">
                <span class="text-2xl">🚗</span>
                <span class="text-lg font-bold text-slate-900">{{ property.garage_spaces }}</span>
                <span class="text-xs text-slate-500">Garage</span>
              </div>
              <div v-if="property.floor_area" class="flex flex-col items-center gap-1 text-center">
                <span class="text-2xl">📐</span>
                <span class="text-lg font-bold text-slate-900">{{ property.floor_area }}<span class="text-sm font-normal"> sqm</span></span>
                <span class="text-xs text-slate-500">Floor Area</span>
              </div>
              <div v-if="property.lot_area" class="flex flex-col items-center gap-1 text-center">
                <span class="text-2xl">🌿</span>
                <span class="text-lg font-bold text-slate-900">{{ property.lot_area }}<span class="text-sm font-normal"> sqm</span></span>
                <span class="text-xs text-slate-500">Lot Area</span>
              </div>
            </div>

            <!-- About -->
            <section v-if="property.description" class="mb-8">
              <h2 class="mb-3 text-lg font-semibold text-slate-900">About this property</h2>
              <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ property.description }}</p>
            </section>

            <hr v-if="property.features?.length" class="mb-8 border-slate-100" />

            <!-- Features -->
            <section v-if="property.features?.length" class="mb-8">
              <h2 class="mb-4 text-lg font-semibold text-slate-900">Features & Amenities</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="feature in property.features"
                  :key="feature"
                  class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 ring-1 ring-teal-200"
                >
                  {{ feature }}
                </span>
              </div>
            </section>

            <hr class="mb-8 border-slate-100" />

            <!-- Property details table -->
            <section class="mb-8">
              <h2 class="mb-4 text-lg font-semibold text-slate-900">Property Details</h2>
              <div class="overflow-hidden rounded-xl border border-slate-100">
                <table class="w-full text-sm">
                  <tbody class="divide-y divide-slate-50">
                    <tr v-if="property.property_type" class="bg-white">
                      <td class="w-1/3 px-4 py-3 font-medium text-slate-500">Type</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ typeLabel[property.property_type] ?? property.property_type }}</td>
                    </tr>
                    <tr v-if="property.listing_type" class="bg-slate-50/60">
                      <td class="px-4 py-3 font-medium text-slate-500">Listing</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ listingLabel[property.listing_type] ?? property.listing_type }}</td>
                    </tr>
                    <tr v-if="property.year_built" class="bg-white">
                      <td class="px-4 py-3 font-medium text-slate-500">Year Built</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ property.year_built }}</td>
                    </tr>
                    <tr v-if="property.floors" class="bg-slate-50/60">
                      <td class="px-4 py-3 font-medium text-slate-500">Floors</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ property.floors }}</td>
                    </tr>
                    <tr v-if="property.floor_area" class="bg-white">
                      <td class="px-4 py-3 font-medium text-slate-500">Floor Area</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ property.floor_area }} sqm</td>
                    </tr>
                    <tr v-if="property.lot_area" class="bg-slate-50/60">
                      <td class="px-4 py-3 font-medium text-slate-500">Lot Area</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ property.lot_area }} sqm</td>
                    </tr>
                    <tr v-if="property.zip_code" class="bg-white">
                      <td class="px-4 py-3 font-medium text-slate-500">ZIP Code</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ property.zip_code }}</td>
                    </tr>
                    <tr v-if="fullAddress" class="bg-slate-50/60">
                      <td class="px-4 py-3 font-medium text-slate-500">Address</td>
                      <td class="px-4 py-3 font-semibold text-slate-800">{{ fullAddress }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Video tour -->
            <section v-if="property.video_url" class="mb-8">
              <h2 class="mb-3 text-lg font-semibold text-slate-900">Video Tour</h2>
              <a
                :href="property.video_url"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-medium text-teal-700 transition-colors hover:bg-teal-100"
              >
                ▶ Watch Video Tour
              </a>
            </section>

            <!-- Nearby places -->
            <section v-if="property.nearby_places?.length" class="mb-8">
              <h2 class="mb-4 text-lg font-semibold text-slate-900">Nearby Places</h2>
              <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <li
                  v-for="place in property.nearby_places"
                  :key="place.name ?? place"
                  class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"
                >
                  <MapPinIcon class="size-4 shrink-0 text-teal-500" />
                  {{ place.name ?? place }}
                </li>
              </ul>
            </section>
          </div>

          <!-- Right: sticky sidebar -->
          <div class="space-y-5 lg:sticky lg:top-24 self-start">
            <!-- Agent card -->
            <div v-if="property.store" class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
              <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-100">Listed by</p>
                <RouterLink
                  :to="`/stores/${property.store.slug}`"
                  class="mt-1 block text-lg font-bold text-white hover:underline"
                >
                  {{ property.store.name }}
                </RouterLink>
              </div>
              <div class="p-5">
                <div class="mb-4 flex items-start gap-3">
                  <img
                    v-if="property.store.agent_photo"
                    :src="property.store.agent_photo"
                    class="size-12 rounded-full object-cover ring-2 ring-teal-100"
                  />
                  <UserCircleIcon v-else class="size-12 shrink-0 text-slate-300" />
                  <div>
                    <p v-if="property.store.agent_name" class="font-semibold text-slate-800">{{ property.store.agent_name }}</p>
                    <p v-if="property.store.agent_bio" class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ property.store.agent_bio }}</p>
                  </div>
                </div>
                <a
                  v-if="property.store.phone"
                  :href="`tel:${property.store.phone}`"
                  class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 py-2.5 text-sm font-medium text-white transition-colors hover:bg-teal-700"
                >
                  <PhoneIcon class="size-4" />
                  {{ property.store.phone }}
                </a>
              </div>
            </div>

            <!-- Inquiry form -->
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
              <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-semibold text-slate-900">Send an Inquiry</h3>
                <p class="mt-0.5 text-xs text-slate-500">Get more details about this property</p>
              </div>
              <div class="p-5">
                <!-- Success state -->
                <div
                  v-if="inquirySuccess"
                  class="flex flex-col items-center gap-2 rounded-xl bg-emerald-50 px-4 py-6 text-center"
                >
                  <CheckCircleIcon class="size-10 text-emerald-500" />
                  <p class="font-semibold text-emerald-800">Inquiry sent!</p>
                  <p class="text-xs text-emerald-600">We'll be in touch with you shortly.</p>
                  <button
                    class="mt-2 text-xs text-emerald-700 underline hover:text-emerald-900"
                    @click="inquirySuccess = false"
                  >
                    Send another inquiry
                  </button>
                </div>

                <!-- Form -->
                <form v-else class="space-y-3" @submit.prevent="submitInquiry">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Full Name *</label>
                    <input
                      v-model="inquiry.name"
                      required
                      type="text"
                      placeholder="Juan dela Cruz"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition-colors focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Email Address *</label>
                    <input
                      v-model="inquiry.email"
                      required
                      type="email"
                      placeholder="juan@example.com"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition-colors focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Phone Number</label>
                    <input
                      v-model="inquiry.phone"
                      type="tel"
                      placeholder="+63 9XX XXX XXXX"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition-colors focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Message</label>
                    <textarea
                      v-model="inquiry.message"
                      rows="3"
                      placeholder="I'm interested in this property. Please send me more details..."
                      class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition-colors focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                    />
                  </div>
                  <div
                    v-if="inquiryError"
                    class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"
                  >
                    <ExclamationCircleIcon class="size-4 shrink-0" />
                    {{ inquiryError }}
                  </div>
                  <button
                    type="submit"
                    :disabled="inquirySubmitting"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    <span v-if="inquirySubmitting">Sending…</span>
                    <span v-else>Send Inquiry</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
