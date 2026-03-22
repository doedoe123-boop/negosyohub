<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  MagnifyingGlassIcon,
  FunnelIcon,
  HomeModernIcon,
  BookmarkIcon,
  ScaleIcon,
  XMarkIcon,
  CheckCircleIcon,
} from "@heroicons/vue/24/outline";
import { ScaleIcon as ScaleSolid } from "@heroicons/vue/24/solid";
import { propertiesApi } from "@/api/properties";
import { savedSearchesApi } from "@/api/savedSearches";
import { useAuthStore } from "@/stores/auth";
import { useCityStore } from "@/stores/city";
import { useCompare } from "@/composables/useCompare";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const cityStore = useCityStore();
const { compareList, canAdd, isInCompare, addToCompare, removeFromCompare } =
  useCompare();

const properties = ref([]);
const meta = ref({});
const loading = ref(true);

const filters = ref({
  search: route.query.search ?? "",
  type: route.query.type ?? "",
  listing_type: route.query.listing_type ?? "",
  min_price: route.query.min_price ?? "",
  max_price: route.query.max_price ?? "",
  bedrooms: route.query.bedrooms ?? "",
  city: route.query.city ?? "",
});

const propertyTypes = [
  { label: "All Types", value: "" },
  { label: "House & Lot", value: "house" },
  { label: "Condominium", value: "condo" },
  { label: "Apartment", value: "apartment" },
  { label: "Townhouse", value: "townhouse" },
  { label: "Commercial Space", value: "commercial" },
  { label: "Vacant Lot", value: "lot" },
  { label: "Warehouse", value: "warehouse" },
  { label: "Farm / Agricultural", value: "farm" },
];

const listingTypes = [
  { label: "All Listings", value: "" },
  { label: "For Sale", value: "for_sale" },
  { label: "For Rent", value: "for_rent" },
  { label: "For Lease", value: "for_lease" },
  { label: "Pre-Selling", value: "pre_selling" },
];

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

const periodMap = {
  per_month: "/mo",
  per_year: "/yr",
  per_sqm: "/sqm",
  per_night: "/night",
  per_day: "/day",
};

function formatPrice(price, currency = "PHP", period = null) {
  if (!price && price !== 0) return "—";
  const formatted = parseFloat(price).toLocaleString("en-PH", {
    style: "currency",
    currency: currency || "PHP",
    maximumFractionDigits: 0,
  });
  if (!period) return formatted;
  const periodStr = periodMap[period] ?? `/${period.replace(/_/g, " ")}`;
  return `${formatted}${periodStr}`;
}

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await propertiesApi.list({
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== ""),
      ),
      page,
    });
    properties.value = data.data ?? data;
    meta.value = data.meta ?? {};
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  router.replace({
    query: Object.fromEntries(
      Object.entries(filters.value).filter(([, v]) => v !== ""),
    ),
  });
  load();
}

function resetFilters() {
  filters.value = {
    search: "",
    type: "",
    listing_type: "",
    min_price: "",
    max_price: "",
    bedrooms: "",
    city: "",
  };
  router.replace({ query: {} });
  load();
}

onMounted(() => {
  // Pre-fill city from store when no URL query param is present
  if (!filters.value.city && cityStore.activeCity) {
    filters.value.city = cityStore.activeCity;
  }
  load();
});
watch(
  () => route.query,
  () => load(),
);

// ── Save Search ─────────────────────────────────────────────────────
const saveSearchModalOpen = ref(false);
const saveSearchName = ref("");
const saveSearchFrequency = ref("weekly");
const saveSearchSubmitting = ref(false);
const saveSearchSuccess = ref(false);

function openSaveSearchModal() {
  if (!auth.isAuthenticated) {
    router.push("/login?redirect=/properties");
    return;
  }
  saveSearchName.value =
    filters.value.search || filters.value.city || "My Search";
  saveSearchSuccess.value = false;
  saveSearchModalOpen.value = true;
}

async function submitSaveSearch() {
  saveSearchSubmitting.value = true;
  try {
    const criteria = Object.fromEntries(
      Object.entries(filters.value).filter(([, v]) => v !== ""),
    );
    await savedSearchesApi.create({
      name: saveSearchName.value,
      criteria,
      notify_frequency: saveSearchFrequency.value,
      is_active: true,
    });
    saveSearchSuccess.value = true;
  } catch {
    // ignore
  } finally {
    saveSearchSubmitting.value = false;
  }
}

const hasActiveFilters = computed(() =>
  Object.values(filters.value).some((v) => v !== ""),
);
</script>

<template>
  <div class="theme-page">
    <!-- Page header -->
    <div class="py-12 text-white" style="background: #0f2044">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <RouterLink
          to="/"
          class="mb-4 inline-flex items-center gap-1 text-sm text-white/60 hover:text-white transition-colors"
        >
          ← Home
        </RouterLink>
        <h1 class="text-3xl font-bold">Browse Properties</h1>
        <p class="mt-1 text-white/70">
          Find your next home, investment, or commercial space.
        </p>
      </div>
    </div>

    <div class="theme-page-section mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <div class="mb-5 flex flex-wrap items-center gap-2">
        <RouterLink
          to="/properties"
          class="theme-tab-active rounded-full px-4 py-2 text-sm font-semibold"
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
          class="theme-tab rounded-full px-4 py-2 text-sm font-semibold"
        >
          Compare
          <span v-if="compareList.length" class="ml-1">({{ compareList.length }})</span>
        </RouterLink>
      </div>

      <!-- Listing type quick-filter pills -->
      <div class="mb-5 flex flex-wrap gap-2">
        <button
          v-for="l in listingTypes"
          :key="l.value"
          type="button"
          class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
          :class="
            filters.listing_type === l.value
              ? 'chip-filter-active'
              : 'chip-filter'
          "
          @click="
            filters.listing_type = l.value;
            onSearch();
          "
        >
          {{ l.label }}
        </button>
      </div>

      <!-- Filters -->
      <form
        class="theme-card mb-8 rounded-2xl p-4"
        @submit.prevent="onSearch"
      >
        <div class="relative mb-3">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 theme-copy"
          />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Search by title, city, or address…"
            class="theme-input w-full rounded-xl py-2.5 pl-9 pr-4 text-sm"
          />
        </div>

        <div
          class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center"
        >
          <select
            v-model="filters.type"
            class="theme-input col-span-2 rounded-xl px-3 py-2 text-sm sm:w-auto"
          >
            <option v-for="t in propertyTypes" :key="t.value" :value="t.value">
              {{ t.label }}
            </option>
          </select>

          <input
            v-model="filters.bedrooms"
            type="number"
            min="1"
            placeholder="Min bedrooms"
            class="theme-input rounded-xl px-3 py-2 text-sm sm:w-32"
          />

          <input
            v-model="filters.min_price"
            type="number"
            min="0"
            placeholder="Min price"
            class="theme-input rounded-xl px-3 py-2 text-sm sm:w-32"
          />

          <input
            v-model="filters.max_price"
            type="number"
            min="0"
            placeholder="Max price"
            class="theme-input rounded-xl px-3 py-2 text-sm sm:w-32"
          />

          <input
            v-model="filters.city"
            type="text"
            placeholder="City"
            class="theme-input rounded-xl px-3 py-2 text-sm sm:w-36"
          />

          <button
            type="submit"
            class="flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
          >
            <FunnelIcon class="size-4" />
            Search
          </button>

          <button
            type="button"
            class="btn-secondary rounded-xl px-4 py-2 text-sm transition-colors"
            @click="resetFilters"
          >
            Reset
          </button>

          <button
            v-if="hasActiveFilters"
            type="button"
            class="flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-100"
            @click="openSaveSearchModal"
          >
            <BookmarkIcon class="size-4" />
            Save Search
          </button>
        </div>
      </form>

      <!-- Skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="theme-card animate-pulse overflow-hidden rounded-2xl"
        >
          <div class="aspect-[16/9]" style="background-color: color-mix(in srgb, var(--color-border) 80%, transparent)" />
          <div class="p-4 space-y-3">
            <div class="h-4 w-3/4 rounded" style="background-color: color-mix(in srgb, var(--color-border) 80%, transparent)" />
            <div class="h-3 w-1/2 rounded" style="background-color: var(--color-surface-muted)" />
            <div class="h-5 w-1/3 rounded" style="background-color: color-mix(in srgb, var(--color-border) 80%, transparent)" />
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="properties.length === 0"
        class="theme-card flex flex-col items-center justify-center rounded-2xl border-dashed py-20 text-center"
      >
        <HomeModernIcon class="theme-copy mb-3 size-10" />
        <p class="theme-copy font-medium">No properties found</p>
        <p class="theme-copy mt-1 text-sm">
          Try adjusting your filters or
          <button
            class="text-emerald-600 underline underline-offset-2"
            @click="resetFilters"
          >
            reset all
          </button>
        </p>
        <p
          v-if="cityStore.activeCity && filters.city"
          class="theme-copy mt-3 text-sm"
        >
          No listings found in
          <strong class="theme-title">{{ cityStore.activeCity }}</strong
          >.
          <button
            class="ml-1 text-emerald-600 underline underline-offset-2"
            @click="
              cityStore.clearAll();
              resetFilters();
            "
          >
            Browse all Philippines
          </button>
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="property in properties"
          :key="property.id"
          class="theme-card theme-card-hover group flex flex-col overflow-hidden rounded-2xl transition-shadow"
        >
          <RouterLink :to="`/properties/${property.slug}`" class="flex-1">
            <div
              class="relative aspect-[16/9] overflow-hidden"
              style="background-color: var(--color-surface-muted)"
            >
              <img
                v-if="property.images && property.images[0]"
                :src="property.images[0]"
                :alt="property.title"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
              />
              <div
                v-else
                class="flex h-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200"
              >
                <HomeModernIcon class="theme-copy size-12" />
              </div>
              <span
                class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="
                  listingBadgeClass[property.listing_type] ??
                  'theme-card-muted'
                "
              >
                {{
                  listingLabel[property.listing_type] ?? property.listing_type
                }}
              </span>
            </div>

            <div class="p-4">
              <p
                class="theme-title mb-1 line-clamp-2 font-semibold transition-colors group-hover:text-emerald-700"
              >
                {{ property.title }}
              </p>
              <p class="theme-copy mb-2 text-xs">
                {{ property.city
                }}{{ property.province ? `, ${property.province}` : "" }}
              </p>
              <div
                v-if="property.bedrooms || property.floor_area"
                class="theme-copy mb-3 flex flex-wrap gap-3 text-xs"
              >
                <span v-if="property.bedrooms"
                  >🛏 {{ property.bedrooms }} BR</span
                >
                <span v-if="property.bathrooms"
                  >🚿 {{ property.bathrooms }} BA</span
                >
                <span v-if="property.floor_area"
                  >📐 {{ property.floor_area }} sqm</span
                >
              </div>
              <p class="text-lg font-bold text-brand-500">
                {{
                  formatPrice(
                    property.price,
                    property.price_currency,
                    property.price_period,
                  )
                }}
              </p>
            </div>
          </RouterLink>

          <!-- Compare footer -->
          <div class="theme-divider-soft flex items-center border-t px-4 py-2">
            <button
              class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
              :class="
                isInCompare(property.id)
                  ? 'bg-emerald-50 text-emerald-700'
                  : !canAdd
                    ? 'cursor-not-allowed theme-copy opacity-50'
                    : 'theme-copy hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text)]'
              "
              :disabled="!canAdd && !isInCompare(property.id)"
              @click="
                isInCompare(property.id)
                  ? removeFromCompare(property.id)
                  : addToCompare(property)
              "
            >
              <ScaleSolid v-if="isInCompare(property.id)" class="size-3.5" />
              <ScaleIcon v-else class="size-3.5" />
              {{ isInCompare(property.id) ? "Added to compare" : "Compare" }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="meta.last_page > 1"
        class="mt-8 flex items-center justify-center gap-2"
      >
        <button
          v-for="page in meta.last_page"
          :key="page"
          class="size-9 rounded-xl border text-sm font-medium transition-colors"
          :class="
            meta.current_page === page
              ? 'chip-filter-active'
              : 'chip-filter'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Compare floating bar -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-transform duration-300"
        enter-from-class="translate-y-full"
        leave-active-class="transition-transform duration-300"
        leave-to-class="translate-y-full"
      >
        <div
          v-if="compareList.length"
          class="theme-floating-bar fixed bottom-0 inset-x-0 z-40"
        >
          <div
            class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6"
          >
            <div class="flex flex-1 items-center gap-3 overflow-x-auto">
              <span
                class="theme-copy shrink-0 text-xs font-semibold uppercase tracking-wide"
              >
                Compare ({{ compareList.length }}/3)
              </span>
              <div
                v-for="item in compareList"
                :key="item.id"
                class="theme-card-muted flex shrink-0 items-center gap-2 rounded-xl px-3 py-1.5 text-xs"
              >
                <span
                  class="theme-title max-w-[120px] truncate font-medium"
                  >{{ item.title }}</span
                >
                <button
                  class="theme-copy hover:text-red-500"
                  @click="removeFromCompare(item.id)"
                >
                  <XMarkIcon class="size-3.5" />
                </button>
              </div>
            </div>
            <RouterLink
              v-if="compareList.length >= 2"
              to="/properties/compare"
              class="shrink-0 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-emerald-700"
            >
              Compare Now
            </RouterLink>
          </div>
        </div>
      </Transition>

      <!-- Save Search Modal -->
      <div
        v-if="saveSearchModalOpen"
        class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="saveSearchModalOpen = false"
      >
        <div
          class="theme-modal w-full max-w-sm rounded-2xl p-6"
        >
          <h3 class="theme-title mb-1 text-lg font-bold">
            Save this Search
          </h3>
          <p class="theme-copy mb-4 text-sm">
            Get notified when new matching properties are listed.
          </p>

          <div
            v-if="saveSearchSuccess"
            class="flex flex-col items-center gap-3 py-4 text-center"
          >
            <CheckCircleIcon class="size-10 text-emerald-500" />
            <p class="theme-title font-bold">Search saved!</p>
            <p class="theme-copy text-sm">
              You'll be notified of new matches.
            </p>
            <button
              class="btn-secondary mt-2 rounded-xl px-5 py-2 text-sm font-semibold"
              @click="saveSearchModalOpen = false"
            >
              Close
            </button>
          </div>

          <form
            v-else
            class="flex flex-col gap-3"
            @submit.prevent="submitSaveSearch"
          >
            <div>
              <label class="theme-copy mb-1 block text-xs font-semibold"
                >Search Name *</label
              >
              <input
                v-model="saveSearchName"
                required
                type="text"
                class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                placeholder="e.g. 3BR Condo in Makati"
              />
            </div>
            <div>
              <label class="theme-copy mb-1 block text-xs font-semibold"
                >Notify me</label
              >
              <select
                v-model="saveSearchFrequency"
                class="theme-input w-full rounded-xl px-3 py-2 text-sm"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="never">Never (save only)</option>
              </select>
            </div>
            <div class="flex gap-2 pt-1">
              <button
                type="button"
                class="btn-secondary flex-1 rounded-xl py-2.5 text-sm font-semibold"
                @click="saveSearchModalOpen = false"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saveSearchSubmitting"
                class="flex-1 rounded-xl bg-emerald-600 py-2.5 text-sm font-bold text-white transition-colors hover:bg-emerald-700 disabled:opacity-60"
              >
                {{ saveSearchSubmitting ? "Saving..." : "Save Search" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
