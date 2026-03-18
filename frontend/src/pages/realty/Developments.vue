<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  MagnifyingGlassIcon,
  BuildingOffice2Icon,
} from "@heroicons/vue/24/outline";
import { developmentsApi } from "@/api/developments";

const route = useRoute();
const router = useRouter();

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

onMounted(() => load());
watch(
  () => route.query,
  () => load(),
);
</script>

<template>
  <div>
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
      <!-- Filters -->
      <form
        class="mb-8 flex flex-wrap items-center gap-2 rounded-2xl border bg-white p-4 shadow-sm"
        @submit.prevent="onSearch"
      >
        <div class="relative flex-1 min-w-[200px]">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Search developments…"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400"
          />
        </div>
        <input
          v-model="filters.city"
          type="text"
          placeholder="City"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:w-36"
        />
        <select
          v-model="filters.type"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400"
        >
          <option v-for="t in developmentTypes" :key="t.value" :value="t.value">
            {{ t.label }}
          </option>
        </select>
        <button
          type="submit"
          class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700"
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
          class="animate-pulse overflow-hidden rounded-2xl border bg-white shadow-sm"
        >
          <div class="aspect-[16/9] bg-slate-200" />
          <div class="p-4 space-y-2">
            <div class="h-4 w-3/4 rounded bg-slate-200" />
            <div class="h-3 w-1/2 rounded bg-slate-100" />
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="developments.length === 0"
        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 py-20 text-center"
      >
        <BuildingOffice2Icon class="mb-3 size-10 text-slate-300" />
        <p class="font-medium text-slate-500">No developments found</p>
        <p class="mt-1 text-sm text-slate-400">
          Try adjusting your search filters.
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="dev in developments"
          :key="dev.id"
          :to="`/developments/${dev.slug}`"
          class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition-shadow hover:shadow-md"
        >
          <!-- Image -->
          <div class="relative aspect-[16/9] overflow-hidden bg-slate-100">
            <img
              v-if="dev.images?.[0]"
              :src="dev.images[0]"
              :alt="dev.name"
              class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200"
            >
              <BuildingOffice2Icon class="size-12 text-slate-300" />
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
              class="absolute bottom-3 right-3 size-10 rounded-xl bg-white object-contain p-1 shadow"
            />
          </div>

          <div class="p-4">
            <p
              class="mb-0.5 line-clamp-1 font-bold text-slate-800 group-hover:text-emerald-700 transition-colors"
            >
              {{ dev.name }}
            </p>
            <p class="text-xs text-slate-500">{{ dev.developer_name }}</p>
            <p class="mt-1 text-xs text-slate-400">
              {{ dev.city }}, {{ dev.province }}
            </p>
            <div class="mt-3 flex items-center justify-between">
              <div>
                <p class="text-[11px] text-slate-400 uppercase tracking-wide">
                  Starting from
                </p>
                <p class="font-bold text-[#F95D2F]">{{ dev.price_range }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-slate-500">
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
              : 'bg-white text-slate-600 hover:bg-slate-50'
          "
          @click="load(page)"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>
