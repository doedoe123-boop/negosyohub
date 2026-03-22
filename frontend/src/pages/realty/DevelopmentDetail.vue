<script setup>
import { computed, ref, onMounted } from "vue";
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

const normalizedDescription = computed(() => {
  const description = development.value?.description;

  if (!description) {
    return "";
  }

  if (/<[a-z][\s\S]*>/i.test(description)) {
    return description;
  }

  return description
    .split(/\n{2,}/)
    .map((paragraph) => `<p>${paragraph.replace(/\n/g, "<br>")}</p>`)
    .join("");
});

const mapCoordinates = computed(() => {
  const latitude = Number(development.value?.latitude);
  const longitude = Number(development.value?.longitude);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null;
  }

  return { latitude, longitude };
});

const mapEmbedUrl = computed(() => {
  if (!mapCoordinates.value) {
    return null;
  }

  const { latitude, longitude } = mapCoordinates.value;
  return `https://www.google.com/maps?q=${latitude},${longitude}&z=15&output=embed`;
});

const mapExternalUrl = computed(() => {
  if (mapCoordinates.value) {
    const { latitude, longitude } = mapCoordinates.value;
    return `https://www.google.com/maps?q=${latitude},${longitude}`;
  }

  if (development.value?.full_location) {
    return `https://www.google.com/maps?q=${encodeURIComponent(development.value.full_location)}`;
  }

  return null;
});

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
  <div class="theme-page min-h-screen">
    <!-- Loading skeleton -->
    <div
      v-if="loading"
      class="mx-auto max-w-6xl animate-pulse px-4 py-12 sm:px-6"
    >
      <div class="theme-skeleton mb-8 h-[320px] rounded-2xl" />
      <div class="theme-skeleton mb-3 h-8 w-2/3 rounded" />
      <div class="theme-skeleton h-4 w-1/3 rounded" />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6"
    >
      <p class="theme-copy">{{ error }}</p>
      <RouterLink
        to="/developments"
        class="btn-primary mt-4 inline-flex rounded-xl px-5 py-2.5 text-sm font-bold"
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
        <nav class="theme-copy mb-6 flex items-center gap-1.5 text-xs">
          <RouterLink to="/" class="hover:text-[var(--color-text)]">Home</RouterLink>
          <span>/</span>
          <RouterLink to="/developments" class="hover:text-[var(--color-text)]"
            >Developments</RouterLink
          >
          <span>/</span>
          <span class="theme-title">{{ development.name }}</span>
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
                  class="theme-card size-16 shrink-0 rounded-2xl object-contain p-2 shadow-sm"
                />
                <div>
                  <h1 class="theme-title text-3xl font-extrabold">
                    {{ development.name }}
                  </h1>
                  <p class="theme-copy mt-0.5">
                    by {{ development.developer_name }}
                  </p>
                  <p
                    class="theme-copy mt-2 flex items-center gap-1 text-sm"
                  >
                    <MapPinIcon class="size-4 text-emerald-500" />
                    {{ development.full_location }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Description -->
            <section v-if="development.description">
              <h2 class="theme-title mb-2 text-lg font-bold">
                About this Development
              </h2>
              <div
                class="prose prose-sm max-w-none"
                style="color: var(--color-text-muted)"
                v-html="normalizedDescription"
              ></div>
            </section>

            <!-- Amenities -->
            <section v-if="development.amenities?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">Amenities</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="amenity in development.amenities"
                  :key="amenity"
                  class="theme-card rounded-full px-3 py-1 text-xs font-medium"
                >
                  {{ amenity }}
                </span>
              </div>
            </section>

            <!-- Map -->
            <section v-if="mapEmbedUrl || mapExternalUrl">
              <h2 class="theme-title mb-3 text-lg font-bold">Location</h2>
              <div class="theme-card overflow-hidden rounded-2xl shadow-sm">
                <iframe
                  v-if="mapEmbedUrl"
                  :src="mapEmbedUrl"
                  class="h-[280px] w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                ></iframe>
                <div
                  v-else
                  class="theme-card-muted flex h-[280px] items-center justify-center p-6 text-center"
                >
                  <div>
                    <p class="theme-title text-sm font-semibold">
                      Map preview unavailable
                    </p>
                    <p class="theme-copy mt-1 text-sm">
                      Open the location in your maps app instead.
                    </p>
                  </div>
                </div>
                <div
                  v-if="mapExternalUrl"
                  class="theme-divider-soft flex items-center justify-between border-t px-4 py-3 text-sm"
                >
                  <span class="theme-copy">Open full map</span>
                  <a
                    :href="mapExternalUrl"
                    target="_blank"
                    rel="noopener"
                    class="font-semibold text-emerald-600 hover:text-emerald-500"
                  >
                    Open in Maps
                  </a>
                </div>
              </div>
            </section>

            <!-- Available Units -->
            <section v-if="development.properties?.length">
              <h2 class="theme-title mb-4 text-lg font-bold">
                Available Units
              </h2>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <RouterLink
                  v-for="property in development.properties"
                  :key="property.id"
                  :to="`/properties/${property.slug}`"
                  class="theme-card theme-card-hover group flex gap-3 overflow-hidden rounded-2xl p-3 shadow-sm transition-shadow"
                >
                  <div
                    class="theme-card-muted aspect-square size-20 shrink-0 overflow-hidden rounded-xl"
                  >
                    <img
                      v-if="property.images?.[0]"
                      :src="property.images[0]"
                      class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                      <HomeModernIcon class="theme-copy size-8" />
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <span
                      class="mb-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                      :class="
                        listingBadgeClass[property.listing_type] ??
                        'theme-badge-neutral'
                      "
                      >{{
                        listingLabel[property.listing_type] ??
                        property.listing_type
                      }}</span
                    >
                    <p
                      class="theme-title line-clamp-2 text-sm font-semibold group-hover:text-emerald-700"
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
            <div class="theme-card rounded-2xl p-5 shadow-lg">
              <h3 class="theme-title mb-4 text-base font-bold">
                Project Details
              </h3>
              <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                  <dt class="theme-copy">Type</dt>
                  <dd class="theme-title font-medium capitalize">
                    {{ development.development_type?.replace(/_/g, " ") }}
                  </dd>
                </div>
                <div
                  v-if="development.floors"
                  class="flex justify-between text-sm"
                >
                  <dt class="theme-copy">Floors</dt>
                  <dd class="theme-title font-medium">
                    {{ development.floors }}
                  </dd>
                </div>
                <div
                  v-if="development.year_built"
                  class="flex justify-between text-sm"
                >
                  <dt class="theme-copy">Year</dt>
                  <dd class="theme-title font-medium">
                    {{ development.year_built }}
                  </dd>
                </div>
                <div
                  v-if="development.total_units"
                  class="flex justify-between text-sm"
                >
                  <dt class="theme-copy">Total Units</dt>
                  <dd class="theme-title font-medium">
                    {{ development.total_units }}
                  </dd>
                </div>
                <div
                  v-if="development.available_units"
                  class="flex justify-between text-sm"
                >
                  <dt class="theme-copy">Available</dt>
                  <dd class="font-semibold text-emerald-600">
                    {{ development.available_units }} units
                  </dd>
                </div>
                <div class="theme-divider-soft border-t pt-3">
                  <p class="theme-copy mb-1 text-xs">Price Range</p>
                  <p class="text-lg font-bold text-[#F95D2F]">
                    {{ development.price_range }}
                  </p>
                </div>
              </dl>

              <div class="mt-5 flex flex-col gap-2">
                <RouterLink
                  :to="`/properties?search=${encodeURIComponent(development.name)}`"
                  class="btn-primary block w-full rounded-xl py-3 text-center text-sm font-bold transition-colors"
                >
                  View All Units
                </RouterLink>
                <a
                  v-if="development.website_url"
                  :href="development.website_url"
                  target="_blank"
                  rel="noopener"
                  class="btn-secondary flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-medium transition-colors"
                >
                  <GlobeAltIcon class="size-4" /> Visit Website
                </a>
                <a
                  v-if="development.video_url"
                  :href="development.video_url"
                  target="_blank"
                  rel="noopener"
                  class="btn-secondary flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-medium transition-colors"
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
