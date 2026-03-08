<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  MagnifyingGlassIcon,
  MapPinIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";

const route = useRoute();
const router = useRouter();

const stores = ref([]);
const meta = ref({});
const loading = ref(true);
const error = ref(false);
const search = ref(route.query.search ?? "");
const sector = ref(route.query.sector ?? "");
const collectionId = ref(route.query.collection_id ?? "");
const searchInputRef = ref(null);

const sectors = [
  { label: "All", value: "" },
  { label: "E-Commerce", value: "ecommerce" },
  { label: "Real Estate", value: "real_estate" },
  { label: "Services", value: "services" },
];

async function load(page = 1) {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await storesApi.list({
      search: search.value || undefined,
      sector: sector.value || undefined,
      collection_id: collectionId.value || undefined,
      page,
    });
    stores.value = data.data ?? data;
    meta.value = data.meta ?? {};
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  router.replace({
    query: {
      search: search.value || undefined,
      sector: sector.value || undefined,
      collection_id: collectionId.value || undefined,
    },
  });
  load();
}

function setSector(value) {
  sector.value = value;
  onSearch();
}

onMounted(() => {
  load();
  if (route.query.focus) {
    nextTick(() => searchInputRef.value?.focus());
  }
});
watch(
  () => route.query,
  (q) => {
    search.value = q.search ?? "";
    sector.value = q.sector ?? "";
    collectionId.value = q.collection_id ?? "";
    load();
  },
);
</script>

<template>
  <div>
    <!-- Page header with integrated search -->
    <div
      class="relative overflow-hidden py-14 text-white"
      style="background: #0f2044"
    >
      <!-- Subtle pattern overlay -->
      <div class="pointer-events-none absolute inset-0 opacity-10">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern
              id="stores-grid"
              width="32"
              height="32"
              patternUnits="userSpaceOnUse"
            >
              <path
                d="M 32 0 L 0 0 0 32"
                fill="none"
                stroke="white"
                stroke-width="0.5"
              />
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#stores-grid)" />
        </svg>
      </div>
      <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
        <RouterLink
          to="/"
          class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-white/60 hover:text-white transition-colors"
        >
          <svg
            class="size-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="m15 19-7-7 7-7"
            />
          </svg>
          Home
        </RouterLink>
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
          Browse Stores
        </h1>
        <p class="mt-2 text-white/70 sm:text-lg">
          Discover trusted local businesses across the Philippines.
        </p>

        <!-- Integrated search bar -->
        <form class="mt-6 flex max-w-xl gap-2" @submit.prevent="onSearch">
          <div class="relative flex-1">
            <MagnifyingGlassIcon
              class="absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-slate-400"
            />
            <input
              ref="searchInputRef"
              v-model="search"
              type="search"
              placeholder="Search stores by name…"
              class="w-full rounded-xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 shadow-lg focus:outline-none focus:ring-2 focus:ring-white/70"
            />
          </div>
          <button
            type="submit"
            class="shrink-0 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500 transition-all"
          >
            Search
          </button>
        </form>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <!-- Sector filter pills -->
      <div class="mb-7 flex flex-wrap gap-2">
        <button
          v-for="s in sectors"
          :key="s.value"
          type="button"
          class="rounded-full px-4 py-1.5 text-sm font-medium transition-all"
          :class="
            sector === s.value
              ? 'bg-brand-500 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-600 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600'
          "
          @click="setSector(s.value)"
        >
          {{ s.label }}
        </button>
      </div>

      <!-- Skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
      >
        <div
          v-for="i in 12"
          :key="i"
          class="h-52 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="rounded-2xl border border-red-100 bg-red-50 py-14 text-center text-sm text-red-600"
      >
        Failed to load stores. Please try again.
        <button
          type="button"
          class="mt-3 block mx-auto text-red-700 underline hover:no-underline"
          @click="load()"
        >
          Retry
        </button>
      </div>

      <!-- Empty -->
      <div
        v-else-if="stores.length === 0"
        class="rounded-2xl border border-dashed border-slate-200 bg-white py-20 text-center"
      >
        <p class="text-2xl mb-3">🔍</p>
        <p class="font-medium text-slate-600">No stores found</p>
        <p class="mt-1 text-sm text-slate-400">
          Try a different search term or sector filter.
        </p>
        <button
          type="button"
          class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
          @click="
            search = '';
            sector = '';
            load();
          "
        >
          Clear filters
        </button>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <RouterLink
          v-for="store in stores"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
        >
          <!-- Banner -->
          <div
            class="relative aspect-[3/2] w-full overflow-hidden bg-slate-100"
          >
            <img
              v-if="store.banner_url"
              :src="store.banner_url"
              :alt="store.name"
              class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full items-center justify-center"
              :class="
                store.sector_template === 'real_estate'
                  ? 'bg-gradient-to-br from-slate-100 to-slate-200'
                  : 'bg-gradient-to-br from-brand-50 to-brand-100'
              "
            >
              <span class="text-3xl">{{
                store.sector_template === "real_estate" ? "🏠" : "🛍️"
              }}</span>
            </div>
            <!-- Sector badge overlay -->
            <span
              v-if="store.sector"
              class="absolute bottom-2 right-2 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold text-white backdrop-blur-sm"
            >
              {{ store.sector_label ?? store.sector }}
            </span>
          </div>
          <!-- Info -->
          <div class="flex items-center justify-between p-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="relative shrink-0">
                <img
                  v-if="store.logo_url"
                  :src="store.logo_url"
                  :alt="store.name"
                  class="size-10 rounded-xl bg-slate-100 object-cover ring-2 ring-white shadow-sm"
                />
                <div
                  v-else
                  class="flex size-10 items-center justify-center rounded-xl bg-slate-100 text-lg"
                >
                  🏪
                </div>
              </div>
              <div class="min-w-0">
                <p
                  class="truncate text-sm font-semibold text-slate-800 group-hover:text-brand-600 transition-colors"
                >
                  {{ store.name }}
                </p>
                <p class="flex items-center gap-0.5 text-xs text-slate-400">
                  <MapPinIcon class="size-3 shrink-0" />
                  {{ store.address?.city ?? store.city ?? "Philippines" }}
                </p>
              </div>
            </div>
            <!-- Arrow indicator -->
            <ChevronRightIcon
              class="size-4 shrink-0 text-slate-300 group-hover:text-brand-500 transition-colors"
            />
          </div>
        </RouterLink>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="mt-10 flex justify-center gap-1.5">
        <button
          v-for="page in meta.last_page"
          :key="page"
          type="button"
          class="size-9 rounded-xl text-sm font-medium transition-all"
          :class="
            meta.current_page === page
              ? 'bg-brand-500 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-600 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>
