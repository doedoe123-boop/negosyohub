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

onMounted(() => load());
watch(
  () => route.query,
  () => load(),
);
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold text-gray-900">Browse Stores</h1>

    <!-- Search bar -->
    <form class="mb-8 flex gap-3" @submit.prevent="onSearch">
      <div class="relative flex-1">
        <MagnifyingGlassIcon
          class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-gray-400"
        />
        <input
          v-model="search"
          type="search"
          placeholder="Search stores…"
          class="w-full rounded-xl border py-3 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <button
        type="submit"
        class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
      >
        Search
      </button>
    </form>

    <!-- Results -->
    <div
      v-if="loading"
      class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
    >
      <div
        v-for="i in 12"
        :key="i"
        class="h-40 animate-pulse rounded-2xl bg-gray-100"
      />
    </div>

    <div
      v-else-if="stores.length === 0"
      class="py-16 text-center text-gray-400"
    >
      No stores found. Try a different search.
    </div>

    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <RouterLink
        v-for="store in stores"
        :key="store.id"
        :to="`/stores/${store.slug}`"
        class="group rounded-2xl border bg-white p-4 shadow-sm hover:shadow-md transition-shadow"
      >
        <img
          :src="store.logo_url ?? '/placeholder.png'"
          :alt="store.name"
          class="mb-3 h-16 w-16 rounded-xl object-cover bg-gray-100"
        />
        <p
          class="font-semibold text-gray-800 group-hover:text-brand-600 transition-colors line-clamp-1"
        >
          {{ store.name }}
        </p>
        <p class="mt-1 text-xs text-gray-500">{{ store.sector }}</p>
      </RouterLink>
    </div>
  </div>
</template>
