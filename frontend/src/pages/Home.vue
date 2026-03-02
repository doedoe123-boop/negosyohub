<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { storesApi } from "@/api/stores";

const featuredStores = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await storesApi.list({ per_page: 8, featured: true });
    featuredStores.value = data.data ?? data;
  } catch {
    featuredStores.value = [];
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div>
    <!-- Hero -->
    <section
      class="bg-gradient-to-br from-brand-600 to-brand-400 py-20 text-center text-white"
    >
      <h1 class="text-4xl font-extrabold sm:text-5xl">
        Your Local Marketplace
      </h1>
      <p class="mt-4 text-lg text-brand-100">
        Food, retail, real estate and more — all in one place.
      </p>
      <div class="mt-8 flex justify-center gap-3">
        <RouterLink
          to="/stores"
          class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-brand-600 hover:bg-brand-50 transition-colors"
        >
          Browse Stores
        </RouterLink>
        <RouterLink
          to="/properties"
          class="rounded-xl border border-white/50 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors"
        >
          View Properties
        </RouterLink>
      </div>
    </section>

    <!-- Featured stores -->
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
      <h2 class="mb-6 text-2xl font-bold text-gray-900">Featured Stores</h2>

      <div v-if="loading" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div
          v-for="i in 8"
          :key="i"
          class="h-36 animate-pulse rounded-2xl bg-gray-100"
        />
      </div>

      <div
        v-else-if="featuredStores.length === 0"
        class="py-12 text-center text-gray-400"
      >
        No featured stores yet. Check back soon!
      </div>

      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <RouterLink
          v-for="store in featuredStores"
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
            class="font-semibold text-gray-800 group-hover:text-brand-600 transition-colors"
          >
            {{ store.name }}
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ store.sector }}</p>
        </RouterLink>
      </div>

      <div class="mt-8 text-center">
        <RouterLink
          to="/stores"
          class="text-sm font-medium text-brand-600 hover:underline"
        >
          View all stores →
        </RouterLink>
      </div>
    </section>
  </div>
</template>
