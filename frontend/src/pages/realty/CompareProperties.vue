<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useCompare } from "@/composables/useCompare";
import { propertiesApi } from "@/api/properties";
import {
  ScaleIcon,
  XMarkIcon,
  HomeModernIcon,
} from "@heroicons/vue/24/outline";

const router = useRouter();
const { compareList, removeFromCompare, clearCompare } = useCompare();

const properties = ref([]);
const loading = ref(true);

function syncVisibleProperties() {
  const visibleIds = new Set(compareList.value.map((item) => String(item.id)));
  properties.value = properties.value.filter((property) =>
    visibleIds.has(String(property.id)),
  );
}

onMounted(async () => {
  try {
    if (compareList.value.length > 0) {
      // Load full property details for each slug in parallel
      const results = await Promise.all(
        compareList.value.map((item) =>
          propertiesApi
            .show(item.slug)
            .then((r) => r.data.data ?? r.data)
            .catch(() => null),
        ),
      );
      properties.value = results.filter(Boolean);
    } else {
      properties.value = [];
    }
  } finally {
    loading.value = false;
  }
});

watch(compareList, () => {
  syncVisibleProperties();
});

function handleRemove(propertyId) {
  removeFromCompare(propertyId);
  syncVisibleProperties();
}

const specs = [
  { label: "Price", key: "formatted_price" },
  {
    label: "Property Type",
    key: "property_type",
    format: (v) => v?.replace(/_/g, " "),
  },
  {
    label: "Listing Type",
    key: "listing_type",
    format: (v) => v?.replace(/_/g, " "),
  },
  { label: "Bedrooms", key: "bedrooms" },
  { label: "Bathrooms", key: "bathrooms" },
  { label: "Garage Spaces", key: "garage_spaces" },
  { label: "Floor Area (sqm)", key: "floor_area" },
  { label: "Lot Area (sqm)", key: "lot_area" },
  { label: "City", key: "city" },
  { label: "Province", key: "province" },
  {
    label: "Virtual Tour",
    key: "virtual_tour_url",
    format: (v) => (v ? "Available" : "—"),
  },
];

function val(property, spec) {
  const v = property[spec.key];
  if (v === null || v === undefined || v === "") return "—";
  return spec.format ? spec.format(v) : v;
}
</script>

<template>
  <div class="theme-page min-h-screen">
    <!-- Header -->
    <div class="py-10 text-white" style="background: #0f2044">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <RouterLink
          to="/properties"
          class="mb-3 inline-flex items-center gap-1 text-sm text-white/60 hover:text-white"
        >
          ← Back to Properties
        </RouterLink>
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="flex items-center gap-2 text-2xl font-bold">
              <ScaleIcon class="size-6" />
              Compare Properties
            </h1>
            <p class="mt-1 text-white/70">
              Side-by-side comparison of {{ properties.length }} listings
            </p>
          </div>
          <button
            class="rounded-xl border border-white/20 px-4 py-2 text-sm text-white/80 transition-colors hover:bg-white/10"
            @click="
              clearCompare();
              router.push('/properties');
            "
          >
            Clear All
          </button>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <div class="mb-5 flex flex-wrap items-center gap-2">
        <RouterLink
          to="/properties"
          class="theme-tab rounded-full px-4 py-2 text-sm font-semibold"
        >
          Properties
        </RouterLink>
        <RouterLink
          to="/developments"
          class="theme-tab rounded-full px-4 py-2 text-sm font-semibold"
        >
          Developments
        </RouterLink>
        <RouterLink
          to="/properties/compare"
          class="theme-tab-active rounded-full px-4 py-2 text-sm font-semibold"
        >
          Compare
          <span v-if="compareList.length" class="ml-1">({{ compareList.length }})</span>
        </RouterLink>
      </div>

      <!-- Redirect hint if empty -->
      <div
        v-if="!loading && properties.length === 0"
        class="theme-empty-state rounded-2xl py-20 text-center"
      >
        <HomeModernIcon class="theme-copy mx-auto mb-3 size-10" />
        <p class="theme-copy font-medium">No properties to compare yet</p>
        <p class="theme-copy mt-1 text-sm">
          Add at least 2 properties from the listings page, then come back here
          for a side-by-side comparison.
        </p>
        <RouterLink
          to="/properties"
          class="btn-primary mt-3 inline-flex rounded-xl px-5 py-2.5 text-sm font-bold"
        >
          Browse Properties
        </RouterLink>
      </div>

      <div v-else>
        <!-- Property header row -->
        <div
          class="mb-4 grid gap-4"
          :style="`grid-template-columns: 180px repeat(${properties.length}, 1fr)`"
        >
          <div />
          <div
            v-for="property in properties"
            :key="property.id"
            class="theme-card relative overflow-hidden rounded-2xl shadow-sm"
          >
            <button
              class="absolute right-2 top-2 z-10 rounded-full p-1 shadow-sm transition-colors hover:bg-red-50 hover:text-red-500"
              style="background-color: color-mix(in srgb, var(--color-surface) 84%, transparent); color: var(--color-text-muted)"
              @click="handleRemove(property.id)"
            >
              <XMarkIcon class="size-4" />
            </button>
            <img
              v-if="property.images?.[0]"
              :src="property.images[0]"
              :alt="property.title"
              class="aspect-[16/9] w-full object-cover"
            />
            <div
              v-else
              class="theme-card-muted flex aspect-[16/9] items-center justify-center"
            >
              <HomeModernIcon class="theme-copy size-10" />
            </div>
            <div class="p-3">
              <p class="theme-title line-clamp-2 text-sm font-semibold">
                {{ property.title }}
              </p>
              <p class="theme-copy mt-0.5 text-xs">
                {{ property.city }}, {{ property.province }}
              </p>
              <RouterLink
                :to="`/properties/${property.slug}`"
                class="btn-primary mt-2 inline-flex rounded-lg px-3 py-1.5 text-xs font-bold"
              >
                View Listing
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- Spec rows (loading skeleton) -->
        <div v-if="loading" class="space-y-2">
          <div
            v-for="i in 8"
            :key="i"
            class="theme-skeleton h-12 animate-pulse rounded-xl"
          />
        </div>

        <!-- Spec rows -->
        <div
          v-else
          class="theme-card overflow-hidden rounded-2xl shadow-sm"
        >
          <div
            v-for="(spec, i) in specs"
            :key="spec.key"
            class="theme-divider-soft grid gap-0 border-b last:border-b-0"
            :style="
              `grid-template-columns: 180px repeat(${properties.length}, 1fr); ${
                i % 2 === 0
                  ? 'background-color: var(--color-surface)'
                  : 'background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)'
              }`
            "
          >
            <div
              class="theme-copy flex items-center px-4 py-3 text-xs font-semibold uppercase tracking-wide"
            >
              {{ spec.label }}
            </div>
            <div
              v-for="property in properties"
              :key="property.id"
              class="theme-title theme-divider-soft flex items-center border-l px-4 py-3 text-sm"
            >
              {{ val(property, spec) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
