<script setup>
import { ref, onMounted } from "vue";
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

onMounted(async () => {
  if (compareList.value.length < 1) {
    router.replace("/properties");
    return;
  }
  try {
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
  } finally {
    loading.value = false;
  }
});

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
  <div class="min-h-screen bg-slate-50">
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
      <!-- Redirect hint if empty -->
      <div
        v-if="!loading && properties.length === 0"
        class="rounded-2xl border border-dashed border-slate-300 py-20 text-center"
      >
        <HomeModernIcon class="mx-auto mb-3 size-10 text-slate-300" />
        <p class="font-medium text-slate-500">No properties to compare</p>
        <RouterLink
          to="/properties"
          class="mt-3 inline-flex rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700"
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
            class="relative overflow-hidden rounded-2xl border bg-white shadow-sm"
          >
            <button
              class="absolute right-2 top-2 z-10 rounded-full bg-white/80 p-1 shadow-sm hover:bg-red-50 hover:text-red-500"
              @click="removeFromCompare(property.id)"
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
              class="flex aspect-[16/9] items-center justify-center bg-slate-100"
            >
              <HomeModernIcon class="size-10 text-slate-300" />
            </div>
            <div class="p-3">
              <p class="line-clamp-2 text-sm font-semibold text-slate-800">
                {{ property.title }}
              </p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ property.city }}, {{ property.province }}
              </p>
              <RouterLink
                :to="`/properties/${property.slug}`"
                class="mt-2 inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700"
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
            class="h-12 animate-pulse rounded-xl bg-slate-100"
          />
        </div>

        <!-- Spec rows -->
        <div
          v-else
          class="overflow-hidden rounded-2xl border bg-white shadow-sm"
        >
          <div
            v-for="(spec, i) in specs"
            :key="spec.key"
            class="grid gap-0 border-b border-slate-100 last:border-b-0"
            :style="`grid-template-columns: 180px repeat(${properties.length}, 1fr)`"
            :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'"
          >
            <div
              class="flex items-center px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              {{ spec.label }}
            </div>
            <div
              v-for="property in properties"
              :key="property.id"
              class="flex items-center border-l border-slate-100 px-4 py-3 text-sm text-slate-800"
            >
              {{ val(property, spec) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
