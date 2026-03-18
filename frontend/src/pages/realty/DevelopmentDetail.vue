<script setup>
import { ref, onMounted } from "vue";
import { RouterLink, useRoute } from "vue-router";
import {
  BuildingOffice2Icon,
  MapPinIcon,
  HomeModernIcon,
  GlobeAltIcon,
  VideoCameraIcon,
} from "@heroicons/vue/24/outline";
import { developmentsApi } from "@/api/developments";
import { useSeoMeta } from "@/composables/useSeoMeta";

const route = useRoute();
const development = ref(null);
const loading = ref(true);
const error = ref(null);
const selectedImage = ref(0);

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

useSeoMeta(() => ({
  title: development.value?.name || null,
  description: development.value?.description || null,
  ogImage: development.value?.images?.[0] ?? null,
}));

onMounted(async () => {
  try {
    const { data } = await developmentsApi.show(route.params.slug);
    development.value = data.data ?? data;
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? "Development not found."
        : "Failed to load development.";
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Loading skeleton -->
    <div
      v-if="loading"
      class="mx-auto max-w-6xl animate-pulse px-4 py-12 sm:px-6"
    >
      <div class="mb-8 h-[320px] rounded-2xl bg-slate-200" />
      <div class="h-8 w-2/3 rounded bg-slate-200 mb-3" />
      <div class="h-4 w-1/3 rounded bg-slate-100" />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6"
    >
      <p class="text-slate-500">{{ error }}</p>
      <RouterLink
        to="/developments"
        class="mt-4 inline-flex rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700"
      >
        Back to Developments
      </RouterLink>
    </div>

    <template v-else-if="development">
      <!-- Hero -->
      <div class="relative">
        <div class="aspect-[21/9] max-h-[420px] overflow-hidden">
          <img
            v-if="development.images?.[selectedImage]"
            :src="development.images[selectedImage]"
            :alt="development.name"
            class="h-full w-full object-cover"
          />
          <div
            v-else
            class="flex h-full items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900"
          >
            <BuildingOffice2Icon class="size-20 text-white/20" />
          </div>
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"
          />
        </div>

        <!-- Image strip -->
        <div
          v-if="development.images?.length > 1"
          class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2"
        >
          <button
            v-for="(img, i) in development.images.slice(0, 6)"
            :key="i"
            class="size-10 overflow-hidden rounded-lg ring-2 transition-all"
            :class="
              selectedImage === i ? 'ring-white' : 'ring-transparent opacity-70'
            "
            @click="selectedImage = i"
          >
            <img :src="img" class="h-full w-full object-cover" />
          </button>
        </div>
      </div>

      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-1.5 text-xs text-slate-500">
          <RouterLink to="/" class="hover:text-slate-700">Home</RouterLink>
          <span>/</span>
          <RouterLink to="/developments" class="hover:text-slate-700"
            >Developments</RouterLink
          >
          <span>/</span>
          <span class="text-slate-800">{{ development.name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
          <!-- LEFT -->
          <div class="flex flex-col gap-8">
            <!-- Header -->
            <div>
              <div class="flex items-start gap-4">
                <img
                  v-if="development.logo_url"
                  :src="development.logo_url"
                  :alt="development.name"
                  class="size-16 shrink-0 rounded-2xl bg-white object-contain p-2 shadow-sm ring-1 ring-slate-200"
                />
                <div>
                  <h1 class="text-3xl font-extrabold text-slate-900">
                    {{ development.name }}
                  </h1>
                  <p class="mt-0.5 text-slate-500">
                    by {{ development.developer_name }}
                  </p>
                  <p
                    class="mt-2 flex items-center gap-1 text-sm text-slate-500"
                  >
                    <MapPinIcon class="size-4 text-emerald-500" />
                    {{ development.full_location }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Description -->
            <section v-if="development.description">
              <h2 class="mb-2 text-lg font-bold text-slate-900">
                About this Development
              </h2>
              <p
                class="whitespace-pre-line text-sm leading-relaxed text-slate-600"
              >
                {{ development.description }}
              </p>
            </section>

            <!-- Amenities -->
            <section v-if="development.amenities?.length">
              <h2 class="mb-3 text-lg font-bold text-slate-900">Amenities</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="amenity in development.amenities"
                  :key="amenity"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700"
                >
                  {{ amenity }}
                </span>
              </div>
            </section>

            <!-- Map -->
            <section v-if="development.latitude && development.longitude">
              <h2 class="mb-3 text-lg font-bold text-slate-900">Location</h2>
              <div
                class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm"
              >
                <iframe
                  :src="`https://www.openstreetmap.org/export/embed.html?bbox=${development.longitude - 0.01},${development.latitude - 0.01},${development.longitude + 0.01},${development.latitude + 0.01}&layer=mapnik&marker=${development.latitude},${development.longitude}`"
                  class="h-[280px] w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                ></iframe>
              </div>
            </section>

            <!-- Available Units -->
            <section v-if="development.properties?.length">
              <h2 class="mb-4 text-lg font-bold text-slate-900">
                Available Units
              </h2>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <RouterLink
                  v-for="property in development.properties"
                  :key="property.id"
                  :to="`/properties/${property.slug}`"
                  class="group flex gap-3 overflow-hidden rounded-2xl border bg-white p-3 shadow-sm transition-shadow hover:shadow-md"
                >
                  <div
                    class="aspect-square size-20 shrink-0 overflow-hidden rounded-xl bg-slate-100"
                  >
                    <img
                      v-if="property.images?.[0]"
                      :src="property.images[0]"
                      class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                      <HomeModernIcon class="size-8 text-slate-300" />
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <span
                      class="mb-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                      :class="
                        listingBadgeClass[property.listing_type] ??
                        'bg-slate-100 text-slate-600'
                      "
                      >{{
                        listingLabel[property.listing_type] ??
                        property.listing_type
                      }}</span
                    >
                    <p
                      class="line-clamp-2 text-sm font-semibold text-slate-800 group-hover:text-emerald-700"
                    >
                      {{ property.title }}
                    </p>
                    <p class="mt-1 font-bold text-[#F95D2F]">
                      {{ property.formatted_price }}
                    </p>
                  </div>
                </RouterLink>
              </div>
            </section>
          </div>

          <!-- RIGHT sidebar -->
          <div class="space-y-4 lg:sticky lg:top-24 self-start">
            <div
              class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg"
            >
              <h3 class="mb-4 text-base font-bold text-slate-900">
                Project Details
              </h3>
              <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                  <dt class="text-slate-500">Type</dt>
                  <dd class="font-medium text-slate-800 capitalize">
                    {{ development.development_type?.replace(/_/g, " ") }}
                  </dd>
                </div>
                <div
                  v-if="development.floors"
                  class="flex justify-between text-sm"
                >
                  <dt class="text-slate-500">Floors</dt>
                  <dd class="font-medium text-slate-800">
                    {{ development.floors }}
                  </dd>
                </div>
                <div
                  v-if="development.year_built"
                  class="flex justify-between text-sm"
                >
                  <dt class="text-slate-500">Year</dt>
                  <dd class="font-medium text-slate-800">
                    {{ development.year_built }}
                  </dd>
                </div>
                <div
                  v-if="development.total_units"
                  class="flex justify-between text-sm"
                >
                  <dt class="text-slate-500">Total Units</dt>
                  <dd class="font-medium text-slate-800">
                    {{ development.total_units }}
                  </dd>
                </div>
                <div
                  v-if="development.available_units"
                  class="flex justify-between text-sm"
                >
                  <dt class="text-slate-500">Available</dt>
                  <dd class="font-semibold text-emerald-600">
                    {{ development.available_units }} units
                  </dd>
                </div>
                <div class="border-t border-slate-100 pt-3">
                  <p class="text-xs text-slate-400 mb-1">Price Range</p>
                  <p class="text-lg font-bold text-[#F95D2F]">
                    {{ development.price_range }}
                  </p>
                </div>
              </dl>

              <div class="mt-5 flex flex-col gap-2">
                <RouterLink
                  :to="`/properties?search=${encodeURIComponent(development.name)}`"
                  class="block w-full rounded-xl bg-emerald-600 py-3 text-center text-sm font-bold text-white transition-colors hover:bg-emerald-700"
                >
                  View All Units
                </RouterLink>
                <a
                  v-if="development.website_url"
                  :href="development.website_url"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                >
                  <GlobeAltIcon class="size-4" /> Visit Website
                </a>
                <a
                  v-if="development.video_url"
                  :href="development.video_url"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                >
                  <VideoCameraIcon class="size-4" /> Watch Video
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
