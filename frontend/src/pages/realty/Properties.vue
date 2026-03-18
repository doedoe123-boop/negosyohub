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
import { useCompare } from "@/composables/useCompare";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
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

onMounted(() => load());
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
  <div>
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

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <!-- Listing type quick-filter pills -->
      <div class="mb-5 flex flex-wrap gap-2">
        <button
          v-for="l in listingTypes"
          :key="l.value"
          type="button"
          class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
          :class="
            filters.listing_type === l.value
              ? 'bg-emerald-600 text-white shadow-sm'
              : 'border border-slate-300 bg-white text-slate-600 hover:border-emerald-400 hover:text-emerald-700'
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
        class="mb-8 rounded-2xl border bg-white p-4 shadow-sm"
        @submit.prevent="onSearch"
      >
        <div class="relative mb-3">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Search by title, city, or address…"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400"
          />
        </div>

        <div
          class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center"
        >
          <select
            v-model="filters.type"
            class="col-span-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:w-auto"
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
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:w-32"
          />

          <input
            v-model="filters.min_price"
            type="number"
            min="0"
            placeholder="Min price"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:w-32"
          />

          <input
            v-model="filters.max_price"
            type="number"
            min="0"
            placeholder="Max price"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:w-32"
          />

          <input
            v-model="filters.city"
            type="text"
            placeholder="City"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-400 sm:w-36"
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
            class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-500 transition-colors hover:text-slate-700"
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
          class="animate-pulse overflow-hidden rounded-2xl border bg-white shadow-sm"
        >
          <div class="aspect-[16/9] bg-slate-200" />
          <div class="p-4 space-y-3">
            <div class="h-4 w-3/4 rounded bg-slate-200" />
            <div class="h-3 w-1/2 rounded bg-slate-100" />
            <div class="h-5 w-1/3 rounded bg-slate-200" />
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="properties.length === 0"
        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 py-20 text-center"
      >
        <HomeModernIcon class="mb-3 size-10 text-slate-300" />
        <p class="font-medium text-slate-500">No properties found</p>
        <p class="mt-1 text-sm text-slate-400">
          Try adjusting your filters or
          <button
            class="text-emerald-600 underline underline-offset-2"
            @click="resetFilters"
          >
            reset all
          </button>
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="property in properties"
          :key="property.id"
          class="group flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition-shadow hover:shadow-md"
        >
          <RouterLink :to="`/properties/${property.slug}`" class="flex-1">
            <div class="relative aspect-[16/9] overflow-hidden bg-slate-100">
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
                <HomeModernIcon class="size-12 text-slate-300" />
              </div>
              <span
                class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="
                  listingBadgeClass[property.listing_type] ??
                  'bg-slate-100 text-slate-600'
                "
              >
                {{
                  listingLabel[property.listing_type] ?? property.listing_type
                }}
              </span>
            </div>

            <div class="p-4">
              <p
                class="mb-1 line-clamp-2 font-semibold text-slate-800 group-hover:text-emerald-700 transition-colors"
              >
                {{ property.title }}
              </p>
              <p class="mb-2 text-xs text-slate-400">
                {{ property.city
                }}{{ property.province ? `, ${property.province}` : "" }}
              </p>
              <div
                v-if="property.bedrooms || property.floor_area"
                class="mb-3 flex flex-wrap gap-3 text-xs text-slate-500"
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
          <div class="flex items-center border-t border-slate-100 px-4 py-2">
            <button
              class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
              :class="
                isInCompare(property.id)
                  ? 'bg-emerald-50 text-emerald-700'
                  : !canAdd
                    ? 'cursor-not-allowed text-slate-300'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
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
              ? 'bg-emerald-600 text-white border-emerald-600'
              : 'bg-white text-slate-600 hover:bg-slate-50'
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
          class="fixed bottom-0 inset-x-0 z-40 border-t border-slate-200 bg-white shadow-xl"
        >
          <div
            class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6"
          >
            <div class="flex flex-1 items-center gap-3 overflow-x-auto">
              <span
                class="shrink-0 text-xs font-semibold text-slate-500 uppercase tracking-wide"
              >
                Compare ({{ compareList.length }}/3)
              </span>
              <div
                v-for="item in compareList"
                :key="item.id"
                class="flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs"
              >
                <span
                  class="max-w-[120px] truncate font-medium text-slate-700"
                  >{{ item.title }}</span
                >
                <button
                  class="text-slate-400 hover:text-red-500"
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="saveSearchModalOpen = false"
      >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
          <h3 class="mb-1 text-lg font-bold text-slate-900">
            Save this Search
          </h3>
          <p class="mb-4 text-sm text-slate-500">
            Get notified when new matching properties are listed.
          </p>

          <div
            v-if="saveSearchSuccess"
            class="flex flex-col items-center gap-3 py-4 text-center"
          >
            <CheckCircleIcon class="size-10 text-emerald-500" />
            <p class="font-bold text-slate-900">Search saved!</p>
            <p class="text-sm text-slate-500">
              You'll be notified of new matches.
            </p>
            <button
              class="mt-2 rounded-xl bg-slate-100 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
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
              <label class="mb-1 block text-xs font-semibold text-slate-600"
                >Search Name *</label
              >
              <input
                v-model="saveSearchName"
                required
                type="text"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                placeholder="e.g. 3BR Condo in Makati"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-600"
                >Notify me</label
              >
              <select
                v-model="saveSearchFrequency"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="never">Never (save only)</option>
              </select>
            </div>
            <div class="flex gap-2 pt-1">
              <button
                type="button"
                class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
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
