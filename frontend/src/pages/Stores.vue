<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";

const route = useRoute();
const router = useRouter();

const stores = ref([]);
const meta = ref({});
const loading = ref(true);
const search = ref(route.query.search ?? "");
const sector = ref(route.query.sector ?? "");

const sectorLabels = {
  ecommerce: "E-Commerce",
  real_estate: "Real Estate",
  services: "Services",
};

const sectors = [
  { label: "All", value: "" },
  { label: "E-Commerce", value: "ecommerce" },
  { label: "Real Estate", value: "real_estate" },
  { label: "Services", value: "services" },
];

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await storesApi.list({
      search: search.value || undefined,
      sector: sector.value || undefined,
      page,
    });
    stores.value = data.data ?? data;
    meta.value = data.meta ?? {};
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  router.replace({
    query: {
      search: search.value || undefined,
      sector: sector.value || undefined,
    },
  });
  load();
}

function setSector(value) {
  sector.value = value;
  onSearch();
}

onMounted(() => load());
watch(
  () => route.query,
  () => load(),
);
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="bg-gradient-to-br from-brand-500 to-brand-700 py-12 text-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <RouterLink
          to="/"
          class="mb-4 inline-flex items-center gap-1 text-sm text-brand-200 hover:text-white transition-colors"
        >
          ← Home
        </RouterLink>
        <h1 class="text-3xl font-bold">Browse Stores</h1>
        <p class="mt-1 text-brand-100">
          Discover local businesses across all sectors.
        </p>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <!-- Search bar -->
      <form class="mb-5 flex gap-2" @submit.prevent="onSearch">
        <div class="relative flex-1">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="search"
            type="search"
            placeholder="Search stores…"
            class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>
        <button
          type="submit"
          class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
        >
          Search
        </button>
      </form>

      <!-- Sector filter pills -->
      <div class="mb-8 flex flex-wrap gap-2">
        <button
          v-for="s in sectors"
          :key="s.value"
          type="button"
          class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
          :class="
            sector === s.value
              ? 'bg-brand-500 text-white shadow-sm'
              : 'border border-slate-300 bg-white text-slate-600 hover:border-brand-400 hover:text-brand-600'
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
          class="h-48 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>

      <!-- Empty -->
      <div
        v-else-if="stores.length === 0"
        class="rounded-2xl border border-dashed border-slate-300 py-16 text-center text-slate-400"
      >
        <p class="text-sm">
          No stores found. Try a different search or sector.
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <RouterLink
          v-for="store in stores"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="group flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-shadow"
        >
          <!-- Banner -->
          <div class="aspect-[3/2] w-full overflow-hidden bg-slate-100">
            <img
              v-if="store.banner_url"
              :src="store.banner_url"
              :alt="store.name"
              class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full items-center justify-center"
              :class="
                store.sector === 'real_estate'
                  ? 'bg-gradient-to-br from-teal-50 to-teal-100'
                  : 'bg-gradient-to-br from-brand-50 to-brand-100'
              "
            >
              <span class="text-3xl">{{
                store.sector === "real_estate" ? "🏠" : "🛍️"
              }}</span>
            </div>
          </div>
          <!-- Info -->
          <div class="flex items-center gap-3 p-3">
            <img
              v-if="store.logo_url"
              :src="store.logo_url"
              :alt="store.name"
              class="size-10 shrink-0 rounded-xl bg-slate-100 object-cover ring-2 ring-white"
            />
            <div class="min-w-0">
              <p
                class="truncate text-sm font-semibold text-slate-800 group-hover:text-brand-600 transition-colors"
              >
                {{ store.name }}
              </p>
              <p class="text-xs text-slate-500">
                {{ sectorLabels[store.sector] ?? store.city }}
              </p>
            </div>
          </div>
        </RouterLink>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="mt-10 flex justify-center gap-2">
        <button
          v-for="page in meta.last_page"
          :key="page"
          type="button"
          class="size-9 rounded-lg text-sm font-medium transition-colors"
          :class="
            meta.current_page === page
              ? 'bg-brand-500 text-white'
              : 'border border-slate-300 bg-white text-slate-600 hover:border-brand-400'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>
