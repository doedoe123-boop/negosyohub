<script setup>
import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { searchApi } from "@/api/search";

const router = useRouter();
const query = ref("");
const activeFilter = ref("all");
const loading = ref(false);
const results = ref(null);
let debounceTimer = null;

const filters = [
  { key: "all", label: "All" },
  { key: "ecommerce", label: "E-Commerce" },
  { key: "real_estate", label: "Real Estate" },
  { key: "services", label: "Services" },
];

// Debounced live search
watch([query, activeFilter], () => {
  clearTimeout(debounceTimer);
  const q = query.value.trim();
  if (!q || q.length < 2) {
    results.value = null;
    return;
  }
  debounceTimer = setTimeout(() => performSearch(q), 300);
});

async function performSearch(q) {
  loading.value = true;
  try {
    const { data } = await searchApi.global({
      q,
      sector: activeFilter.value === "all" ? undefined : activeFilter.value,
      per_section: 5,
    });
    results.value = data;
  } catch {
    results.value = null;
  } finally {
    loading.value = false;
  }
}

function handleSubmit() {
  const q = query.value.trim();
  if (!q) return;

  // Navigate to the appropriate page with the search query
  if (activeFilter.value === "real_estate") {
    router.push({ path: "/properties", query: { search: q } });
  } else {
    router.push({
      path: "/stores",
      query: {
        search: q,
        sector: activeFilter.value === "all" ? undefined : activeFilter.value,
      },
    });
  }
  results.value = null;
}

function goToStore(slug) {
  results.value = null;
  query.value = "";
  router.push(`/stores/${slug}`);
}

function goToProduct(id) {
  results.value = null;
  query.value = "";
  router.push(`/products/${id}`);
}

function goToProperty(slug) {
  results.value = null;
  query.value = "";
  router.push(`/properties/${slug}`);
}

function viewAllStores() {
  const q = query.value.trim();
  results.value = null;
  router.push({
    path: "/stores",
    query: {
      search: q,
      sector: activeFilter.value === "all" ? undefined : activeFilter.value,
    },
  });
}

function viewAllProperties() {
  const q = query.value.trim();
  results.value = null;
  router.push({ path: "/properties", query: { search: q } });
}

const listingLabels = {
  for_sale: "For Sale",
  for_rent: "For Rent",
  for_lease: "For Lease",
  pre_selling: "Pre-Selling",
};

function formatPrice(price, currency = "PHP", period = null) {
  if (!price && price !== 0) return "";
  const formatted = parseFloat(price).toLocaleString("en-PH", {
    style: "currency",
    currency: currency || "PHP",
    maximumFractionDigits: 0,
  });
  const periodMap = {
    per_month: "/mo",
    per_year: "/yr",
    per_sqm: "/sqm",
  };
  if (!period) return formatted;
  return `${formatted}${periodMap[period] ?? ""}`;
}

const hasResults = () => {
  if (!results.value) return false;
  return (
    results.value.stores?.length > 0 ||
    results.value.products?.length > 0 ||
    results.value.properties?.length > 0
  );
};
</script>

<template>
  <section class="relative -mt-8 z-20 px-4 sm:px-6 pb-4">
    <div class="mx-auto max-w-3xl">
      <form
        @submit.prevent="handleSubmit"
        class="relative rounded-2xl bg-white p-3 sm:p-4 shadow-[0_8px_24px_rgba(15,32,68,0.12)] ring-1 ring-slate-100"
      >
        <!-- Search input row -->
        <div class="flex items-center gap-3">
          <MagnifyingGlassIcon class="size-5 shrink-0 text-slate-400" />
          <input
            v-model="query"
            type="text"
            placeholder="Search products, properties, or stores…"
            class="flex-1 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 outline-none sm:text-base"
            autocomplete="off"
          />
          <!-- Loading spinner -->
          <svg
            v-if="loading"
            class="size-5 shrink-0 animate-spin text-emerald-500"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
          <button
            type="submit"
            class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 hover:shadow-emerald-600/25 hover:shadow-md active:scale-[0.98] transition-all"
          >
            Search
          </button>
        </div>

        <!-- Filter pills -->
        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="filter in filters"
            :key="filter.key"
            type="button"
            @click="activeFilter = filter.key"
            class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all"
            :class="
              activeFilter === filter.key
                ? 'bg-navy-900 text-white shadow-sm'
                : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
            "
          >
            {{ filter.label }}
          </button>
        </div>

        <!-- Live search results dropdown -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="-translate-y-1 opacity-0"
          enter-to-class="translate-y-0 opacity-100"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="translate-y-0 opacity-100"
          leave-to-class="-translate-y-1 opacity-0"
        >
          <div
            v-if="results && query.trim().length >= 2"
            class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5"
          >
            <!-- No results -->
            <div
              v-if="!hasResults()"
              class="py-8 text-center text-sm text-slate-400"
            >
              <p class="text-2xl mb-2">🔍</p>
              <p>
                No results found for "<span
                  class="font-medium text-slate-600"
                  >{{ query.trim() }}</span
                >"
              </p>
              <p class="mt-1 text-xs">Try a different search term or filter.</p>
            </div>

            <template v-else>
              <!-- Stores section -->
              <div
                v-if="results.stores?.length"
                class="border-b border-slate-100"
              >
                <div
                  class="flex items-center justify-between px-4 py-2.5 bg-slate-50/80"
                >
                  <span
                    class="text-[11px] font-bold uppercase tracking-widest text-slate-400"
                    >Stores</span
                  >
                  <button
                    type="button"
                    class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700"
                    @click="viewAllStores"
                  >
                    View all →
                  </button>
                </div>
                <button
                  v-for="store in results.stores"
                  :key="'s-' + store.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-emerald-50/50"
                  @click="goToStore(store.slug)"
                >
                  <img
                    v-if="store.logo_url"
                    :src="store.logo_url"
                    :alt="store.name"
                    class="size-9 rounded-lg bg-slate-100 object-cover ring-1 ring-slate-200"
                  />
                  <div
                    v-else
                    class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🏪
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ store.name }}
                    </p>
                    <p class="truncate text-xs text-slate-400">
                      <span
                        v-if="store.sector"
                        class="mr-1.5 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500"
                        >{{ store.sector_label ?? store.sector }}</span
                      >
                      {{ store.city ?? "Philippines" }}
                    </p>
                  </div>
                </button>
              </div>

              <!-- Products section -->
              <div
                v-if="results.products?.length"
                class="border-b border-slate-100"
              >
                <div
                  class="flex items-center justify-between px-4 py-2.5 bg-slate-50/80"
                >
                  <span
                    class="text-[11px] font-bold uppercase tracking-widest text-slate-400"
                    >Products</span
                  >
                </div>
                <button
                  v-for="product in results.products"
                  :key="'p-' + product.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-emerald-50/50"
                  @click="goToProduct(product.id)"
                >
                  <img
                    v-if="product.thumbnail"
                    :src="product.thumbnail"
                    :alt="product.name"
                    class="size-9 rounded-lg bg-slate-100 object-cover ring-1 ring-slate-200"
                  />
                  <div
                    v-else
                    class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🛍️
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ product.name ?? "Untitled Product" }}
                    </p>
                    <p
                      v-if="product.price"
                      class="text-xs font-medium text-brand-500"
                    >
                      {{
                        formatPrice(product.price, product.currency ?? "PHP")
                      }}
                    </p>
                  </div>
                </button>
              </div>

              <!-- Properties section -->
              <div v-if="results.properties?.length">
                <div
                  class="flex items-center justify-between px-4 py-2.5 bg-slate-50/80"
                >
                  <span
                    class="text-[11px] font-bold uppercase tracking-widest text-slate-400"
                    >Properties</span
                  >
                  <button
                    type="button"
                    class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700"
                    @click="viewAllProperties"
                  >
                    View all →
                  </button>
                </div>
                <button
                  v-for="property in results.properties"
                  :key="'pr-' + property.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-emerald-50/50"
                  @click="goToProperty(property.slug)"
                >
                  <img
                    v-if="property.images?.[0]"
                    :src="property.images[0]"
                    :alt="property.title"
                    class="size-9 rounded-lg bg-slate-100 object-cover ring-1 ring-slate-200"
                  />
                  <div
                    v-else
                    class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🏠
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ property.title }}
                    </p>
                    <p class="truncate text-xs text-slate-400">
                      <span
                        v-if="property.listing_type"
                        class="mr-1.5 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600"
                        >{{
                          listingLabels[property.listing_type] ??
                          property.listing_type
                        }}</span
                      >
                      {{ property.city ?? "" }}
                      <span
                        v-if="property.price"
                        class="ml-1 font-medium text-brand-500"
                        >{{
                          formatPrice(
                            property.price,
                            property.price_currency,
                            property.price_period,
                          )
                        }}</span
                      >
                    </p>
                  </div>
                </button>
              </div>
            </template>
          </div>
        </Transition>
      </form>
    </div>
  </section>
</template>
