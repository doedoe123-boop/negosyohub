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
  <div class="theme-page">
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
          class="mb-5 inline-flex items-center gap-1.5 text-sm font-bold text-white/70 hover:text-white transition-colors"
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
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-5xl">
          Browse E-Commerce Stores
        </h1>
        <p class="mt-3 text-white/80 sm:text-lg max-w-2xl leading-relaxed">
          Discover trusted local shops across the Philippines. Curated
          E-Commerce stores with verified products.
        </p>

        <!-- Integrated search bar -->
        <form class="mt-8 flex max-w-xl gap-3" @submit.prevent="onSearch">
          <div class="relative flex-1">
            <MagnifyingGlassIcon
              class="absolute left-4 top-1/2 size-5 -translate-y-1/2"
              style="color: color-mix(in srgb, var(--color-primary) 35%, white 20%)"
            />
            <input
              ref="searchInputRef"
              v-model="search"
              type="search"
              placeholder="Search stores by name or location…"
              class="w-full rounded-2xl py-3.5 pl-12 pr-4 text-sm font-medium shadow-xl theme-input"
              style="
                background-color: var(--color-surface);
                color: var(--color-primary);
                border-color: transparent;
              "
            />
          </div>
          <button
            type="submit"
            class="shrink-0 rounded-2xl bg-brand-500 px-6 py-3.5 text-sm font-bold text-white hover:bg-brand-600 shadow-lg shadow-brand-500/25 transition-all focus:ring-4 focus:ring-brand-500/30 active:scale-[0.98]"
          >
            Search
          </button>
        </form>
      </div>
    </div>

    <div class="theme-page-section mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <!-- Sector filter pills -->
      <div class="mb-7 flex flex-wrap gap-2">
        <button
          v-for="s in sectors"
          :key="s.value"
          type="button"
          class="rounded-full px-4 py-1.5 text-sm font-medium transition-all"
          :class="
            sector === s.value
              ? 'chip-filter-active'
              : 'chip-filter'
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
          class="h-52 animate-pulse rounded-2xl"
          style="background-color: var(--color-surface-muted)"
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
        class="theme-card rounded-2xl border-dashed py-20 text-center"
      >
        <p class="text-2xl mb-3">🔍</p>
        <p class="theme-title font-medium">No stores found</p>
        <p class="theme-copy mt-1 text-sm">
          Try a different search term or sector filter.
        </p>
        <button
          type="button"
          class="btn-secondary mt-5 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition-colors"
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
      <div
        v-else
        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
      >
        <RouterLink
          v-for="store in stores"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="theme-card theme-card-hover group flex flex-col overflow-hidden rounded-3xl transition-all duration-300"
        >
          <!-- Banner -->
          <div
            class="relative h-32 w-full overflow-hidden"
            style="background-color: var(--color-surface-muted)"
          >
            <img
              v-if="store.banner_url"
              :src="store.banner_url"
              :alt="store.name"
              class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full w-full bg-gradient-to-br"
              :class="store.sector_theme ?? 'from-slate-700 to-slate-900'"
            ></div>

            <div
              class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-60"
            ></div>

            <!-- Sector badge overlay -->
            <span
              v-if="store.sector"
              class="absolute top-3 right-3 rounded-full bg-white/90 backdrop-blur-md px-3 py-1 text-[10px] font-bold tracking-wide uppercase text-[#0F2044] shadow-sm"
            >
              {{ store.sector_label ?? store.sector }}
            </span>
          </div>

          <!-- Info -->
          <div class="relative flex flex-col p-5 pt-0">
            <!-- Overlapping Logo -->
            <div class="relative -mt-10 mb-3 flex justify-start">
              <div
                v-if="store.logo_url"
                class="size-20 overflow-hidden rounded-full shadow-md relative z-10 transition-transform group-hover:scale-105"
                style="
                  border: 4px solid var(--color-surface);
                  background-color: var(--color-surface);
                "
              >
                <img
                  :src="store.logo_url"
                  :alt="store.name"
                  class="h-full w-full object-cover"
                />
              </div>
              <div
                v-else
                class="flex size-20 items-center justify-center rounded-full border-4 border-white bg-[#0F2044] text-3xl font-bold text-white shadow-md relative z-10 transition-transform group-hover:scale-105"
              >
                {{ store.name?.charAt(0).toUpperCase() }}
              </div>
            </div>

            <div class="min-w-0">
              <h3
                class="theme-title truncate text-lg font-bold leading-tight transition-colors group-hover:text-brand-600"
              >
                {{ store.name }}
              </h3>
              <p
                v-if="store.tagline"
                class="theme-copy mt-0.5 truncate text-sm font-medium"
              >
                {{ store.tagline }}
              </p>

              <div
                class="theme-copy theme-divider-soft mt-4 flex items-center justify-between border-t pt-4 text-xs font-medium"
              >
                <span class="flex items-center gap-1.5 truncate">
                  <MapPinIcon class="size-4 shrink-0 text-brand-500" />
                  {{ store.address?.city ?? store.city ?? "Philippines" }}
                </span>
                <!-- Arrow indicator -->
                <ChevronRightIcon
                  class="size-5 shrink-0 transition-all group-hover:text-brand-500 group-hover:translate-x-0.5"
                  style="color: var(--color-text-muted)"
                />
              </div>
            </div>
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
              ? 'chip-filter-active'
              : 'chip-filter'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>
