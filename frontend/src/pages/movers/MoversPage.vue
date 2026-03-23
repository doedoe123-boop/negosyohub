<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { TruckIcon, MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { moversApi } from "@/api/movers";
import { useCityStore } from "@/stores/city";

const route = useRoute();
const router = useRouter();
const cityStore = useCityStore();

const movers = ref([]);
const meta = ref({});
const loading = ref(true);

const filters = ref({
  city: route.query.city ?? "",
  province: route.query.province ?? "",
});

async function fetchMovers(page = 1) {
  loading.value = true;
  try {
    const params = { ...filters.value, page, per_page: 12 };
    const res = await moversApi.list(params);
    movers.value = res.data.data ?? res.data;
    meta.value = res.data.meta ?? {};
  } catch {
    movers.value = [];
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  const query = {};
  if (filters.value.city) query.city = filters.value.city;
  if (filters.value.province) query.province = filters.value.province;
  router.replace({ query });
  fetchMovers();
}

watch(
  () => [route.query.city, route.query.province],
  () => {
    filters.value.city = route.query.city ?? "";
    filters.value.province = route.query.province ?? "";
    fetchMovers();
  },
);

onMounted(() => {
  fetchMovers();
});
</script>

<template>
  <div class="theme-page min-h-screen">
    <!-- Header -->
    <div
      class="relative overflow-hidden py-14 text-white"
      style="background: #0f2044"
    >
      <!-- Subtle pattern overlay -->
      <div class="pointer-events-none absolute inset-0 opacity-10">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern
              id="movers-grid"
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
          <rect width="100%" height="100%" fill="url(#movers-grid)" />
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
        <div class="flex items-center gap-4">
          <span
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-500/20 ring-1 ring-violet-400/30"
          >
            <TruckIcon class="h-6 w-6 text-violet-300" />
          </span>
          <div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
              Lipat Bahay
            </h1>
            <p class="mt-1 text-white/70 sm:text-lg">
              Find trusted moving companies near you
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div
      class="theme-divider-soft theme-page-section border-b shadow-sm"
    >
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-4">
        <form class="flex flex-wrap gap-3" @submit.prevent="applyFilters">
          <div
            class="theme-card-muted flex items-center gap-2 rounded-xl px-3 py-2 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all"
          >
            <MagnifyingGlassIcon class="h-4 w-4 text-slate-400 shrink-0" />
            <input
              v-model="filters.city"
              type="text"
              placeholder="City..."
              autocomplete="off"
              class="theme-title w-36 bg-transparent text-sm placeholder-slate-400 outline-none focus:outline-none"
            />
          </div>
          <input
            v-model="filters.province"
            type="text"
            placeholder="Province..."
            autocomplete="off"
            class="theme-card-muted theme-title rounded-xl px-3 py-2 text-sm placeholder-slate-400 outline-none focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 transition-all"
          />
          <button
            type="submit"
            class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-500 transition-all"
          >
            Search
          </button>
        </form>
      </div>
    </div>

    <!-- Listings -->
    <div class="mx-auto max-w-7xl px-4 py-8">
      <div
        v-if="loading"
        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="theme-skeleton h-48 animate-pulse rounded-2xl"
        ></div>
      </div>

      <div
        v-else-if="movers.length === 0"
        class="theme-empty-state rounded-2xl py-20 text-center"
      >
        <TruckIcon class="mx-auto mb-4 h-16 w-16 text-slate-300" />
        <p class="theme-title text-lg font-medium">
          No moving companies found
        </p>
        <p class="theme-copy mt-1 text-sm">
          Try adjusting your search filters
        </p>
        <p
          v-if="cityStore.activeCity && filters.city"
          class="theme-copy mt-3 text-sm"
        >
          No movers available in
          <strong class="theme-title">{{ cityStore.activeCity }}</strong
          >.
          <button
            class="ml-1 text-emerald-600 underline underline-offset-2"
            @click="
              cityStore.clearAll();
              filters.city = '';
              fetchMovers();
            "
          >
            Browse all Philippines
          </button>
        </p>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="mover in movers"
          :key="mover.id"
          :to="{ name: 'movers.show', params: { slug: mover.slug } }"
          class="theme-card theme-card-hover group flex flex-col overflow-hidden rounded-2xl"
        >
          <div class="p-5">
            <div class="flex items-start gap-4">
              <div
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-violet-50 ring-1 ring-violet-200"
              >
                <TruckIcon class="h-7 w-7 text-violet-600" />
              </div>
              <div class="min-w-0">
                <h3
                  class="theme-title truncate font-semibold group-hover:text-emerald-600 transition-colors"
                >
                  {{ mover.name }}
                </h3>
                <p class="theme-copy mt-1 text-sm">
                  {{ mover.city
                  }}<span v-if="mover.province">, {{ mover.province }}</span>
                </p>
                <p
                  v-if="mover.description"
                  class="theme-copy mt-2 line-clamp-2 text-sm"
                >
                  {{ mover.description }}
                </p>
              </div>
            </div>
          </div>
          <div
            class="theme-divider-soft mt-auto flex items-center justify-between border-t px-5 py-3"
          >
            <span
              class="text-sm font-semibold text-emerald-600 group-hover:text-emerald-500"
              >View Details →</span
            >
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
