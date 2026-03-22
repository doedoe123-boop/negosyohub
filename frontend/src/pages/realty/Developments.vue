<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  MagnifyingGlassIcon,
  BuildingOffice2Icon,
} from "@heroicons/vue/24/outline";
import { developmentsApi } from "@/api/developments";
import { useCityStore } from "@/stores/city";

const route = useRoute();
const router = useRouter();
const cityStore = useCityStore();

const developments = ref([]);
const meta = ref({});
const loading = ref(true);

const filters = ref({
  search: route.query.search ?? "",
  city: route.query.city ?? "",
  type: route.query.type ?? "",
});

const developmentTypes = [
  { label: "All Types", value: "" },
  { label: "Condominium", value: "condominium" },
  { label: "Subdivision", value: "subdivision" },
  { label: "Township", value: "township" },
  { label: "Mixed-Use", value: "mixed_use" },
  { label: "Commercial", value: "commercial" },
];

async function load(page = 1) {
  loading.value = true;
  try {
    const params = {
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== ""),
      ),
      page,
    };
    const { data } = await developmentsApi.list(params);
    developments.value = data.data ?? data;
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

onMounted(() => {
  if (!filters.value.city && cityStore.activeCity) {
    filters.value.city = cityStore.activeCity;
  }
  load();
});
watch(
  () => route.query,
  () => load(),
);
</script>

<template>
  <div class="theme-page min-h-screen">
    <!-- Header -->
    <div class="py-12 text-white" style="background: #0f2044">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <RouterLink
          to="/"
          class="mb-4 inline-flex items-center gap-1 text-sm text-white/60 hover:text-white"
        >
          ← Home
        </RouterLink>
        <h1 class="text-3xl font-bold">Developments & Projects</h1>
        <p class="mt-1 text-white/70">
          Browse condominiums, subdivisions, and township developments.
        </p>
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
          class="theme-tab-active rounded-full px-4 py-2 text-sm font-semibold"
        >
          Developments
        </RouterLink>
        <RouterLink
          to="/properties/compare"
          class="theme-tab rounded-full px-4 py-2 text-sm font-semibold"
        >
          Compare
        </RouterLink>
      </div>

      <!-- Filters -->
      <form
        class="theme-card mb-8 flex flex-wrap items-center gap-2 rounded-2xl p-4 shadow-sm"
        @submit.prevent="onSearch"
      >
        <div class="relative flex-1 min-w-[200px]">
          <MagnifyingGlassIcon
            class="theme-copy absolute left-3 top-1/2 size-4 -translate-y-1/2"
          />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Search developments…"
            class="theme-input w-full rounded-xl py-2.5 pl-9 pr-4 text-sm"
          />
        </div>
        <input
          v-model="filters.city"
          type="text"
          placeholder="City"
          class="theme-input rounded-xl px-3 py-2.5 text-sm sm:w-36"
        />
        <select
          v-model="filters.type"
          class="theme-input rounded-xl px-3 py-2.5 text-sm"
        >
          <option v-for="t in developmentTypes" :key="t.value" :value="t.value">
            {{ t.label }}
          </option>
        </select>
        <button
          type="submit"
          class="btn-primary rounded-xl px-5 py-2.5 text-sm font-medium"
        >
          Search
        </button>
      </form>

      <!-- Skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="theme-card animate-pulse overflow-hidden rounded-2xl shadow-sm"
        >
          <div class="theme-skeleton aspect-[16/9]" />
          <div class="p-4 space-y-2">
            <div class="theme-skeleton h-4 w-3/4 rounded" />
            <div class="theme-skeleton h-3 w-1/2 rounded" />
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="developments.length === 0"
        class="theme-empty-state flex flex-col items-center justify-center rounded-2xl py-20 text-center"
      >
        <BuildingOffice2Icon class="theme-copy mb-3 size-10" />
        <p class="theme-copy font-medium">No developments found</p>
        <p class="theme-copy mt-1 text-sm">
          Try adjusting your search filters.
        </p>
        <p
          v-if="cityStore.activeCity && filters.city"
          class="theme-copy mt-3 text-sm"
        >
          No developments in
          <strong class="theme-title">{{ cityStore.activeCity }}</strong
          >.
          <button
            class="ml-1 text-emerald-600 underline underline-offset-2"
            @click="
              cityStore.clearAll();
              filters.city = '';
              load();
            "
          >
            Browse all Philippines
          </button>
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="dev in developments"
          :key="dev.id"
          :to="`/developments/${dev.slug}`"
          class="theme-card theme-card-hover group overflow-hidden rounded-2xl shadow-sm transition-shadow"
        >
          <!-- Image -->
          <div class="theme-card-muted relative aspect-[16/9] overflow-hidden">
            <img
              v-if="dev.images?.[0]"
              :src="dev.images[0]"
              :alt="dev.name"
              class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
              v-else
              class="theme-card-muted flex h-full items-center justify-center"
            >
              <BuildingOffice2Icon class="theme-copy size-12" />
            </div>
            <span
              v-if="dev.is_featured"
              class="absolute left-3 top-3 rounded-full bg-amber-400 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900"
              >Featured</span
            >
            <img
              v-if="dev.logo_url"
              :src="dev.logo_url"
              :alt="`${dev.name} logo`"
              class="theme-card absolute bottom-3 right-3 size-10 rounded-xl object-contain p-1 shadow"
            />
          </div>

          <div class="p-4">
            <p
              class="theme-title mb-0.5 line-clamp-1 font-bold transition-colors group-hover:text-emerald-700"
            >
              {{ dev.name }}
            </p>
            <p class="theme-copy text-xs">{{ dev.developer_name }}</p>
            <p class="theme-copy mt-1 text-xs">
              {{ dev.city }}, {{ dev.province }}
            </p>
            <div class="mt-3 flex items-center justify-between">
              <div>
                <p class="theme-copy text-[11px] uppercase tracking-wide">
                  Starting from
                </p>
                <p class="font-bold text-[#F95D2F]">{{ dev.price_range }}</p>
              </div>
              <div class="text-right">
                <p class="theme-copy text-xs">
                  {{ dev.available_units ?? "—" }} units available
                </p>
              </div>
            </div>
          </div>
        </RouterLink>
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
              : 'btn-secondary'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>
