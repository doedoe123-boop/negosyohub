<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import client from "@/api/client";

const properties = ref([]);
const loading = ref(true);
const filters = ref({
  type: "",
  min_price: "",
  max_price: "",
  bedrooms: "",
  search: "",
});

async function load() {
  loading.value = true;
  try {
    const { data } = await client.get("/api/properties", {
      params: filters.value,
    });
    properties.value = data.data ?? data;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold text-gray-900">Properties</h1>

    <!-- Filters -->
    <form class="mb-8 flex flex-wrap gap-3" @submit.prevent="load">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search properties…"
        class="flex-1 rounded-xl border px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 min-w-48"
      />
      <select
        v-model="filters.type"
        class="rounded-xl border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
      >
        <option value="">All Types</option>
        <option value="house_and_lot">House & Lot</option>
        <option value="condominium">Condominium</option>
        <option value="lot_only">Lot Only</option>
        <option value="commercial">Commercial</option>
      </select>
      <input
        v-model="filters.min_price"
        type="number"
        placeholder="Min Price"
        class="w-32 rounded-xl border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
      />
      <input
        v-model="filters.max_price"
        type="number"
        placeholder="Max Price"
        class="w-32 rounded-xl border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
      />
      <button
        type="submit"
        class="rounded-xl bg-brand-500 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
      >
        Search
      </button>
    </form>

    <!-- Results -->
    <div
      v-if="loading"
      class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
      <div
        v-for="i in 6"
        :key="i"
        class="h-64 animate-pulse rounded-2xl bg-gray-100"
      />
    </div>

    <div
      v-else-if="properties.length === 0"
      class="py-12 text-center text-gray-400"
    >
      No properties found.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <RouterLink
        v-for="property in properties"
        :key="property.id"
        :to="`/properties/${property.slug}`"
        class="group overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-shadow"
      >
        <img
          :src="property.images?.[0] ?? '/placeholder.png'"
          :alt="property.title"
          class="aspect-video w-full object-cover bg-gray-100"
        />
        <div class="p-4">
          <p
            class="font-semibold text-gray-800 group-hover:text-brand-600 line-clamp-1 transition-colors"
          >
            {{ property.title }}
          </p>
          <p class="mt-1 text-xs text-gray-500 capitalize">
            {{ property.type?.replace("_", " ") }} · {{ property.city }}
          </p>
          <p class="mt-2 text-lg font-bold text-brand-600">
            {{ property.price_formatted }}
          </p>
        </div>
      </RouterLink>
    </div>
  </div>
</template>
