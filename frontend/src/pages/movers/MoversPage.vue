<script setup>
import { ref, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { TruckIcon, MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { moversApi } from "@/api/movers";

const route = useRoute();
const router = useRouter();

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

onMounted(() => fetchMovers());
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 py-12 text-white">
      <div class="mx-auto max-w-7xl px-4">
        <div class="flex items-center gap-3">
          <TruckIcon class="h-10 w-10" />
          <div>
            <h1 class="text-3xl font-bold">Lipat Bahay — Moving Services</h1>
            <p class="mt-1 text-blue-100">
              Find trusted moving companies near you
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="border-b bg-white shadow-sm">
      <div class="mx-auto max-w-7xl px-4 py-4">
        <form class="flex flex-wrap gap-3" @submit.prevent="applyFilters">
          <div class="flex items-center gap-2 rounded-lg border px-3 py-2">
            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
            <input
              v-model="filters.city"
              type="text"
              placeholder="City..."
              class="w-36 text-sm outline-none"
            />
          </div>
          <input
            v-model="filters.province"
            type="text"
            placeholder="Province..."
            class="rounded-lg border px-3 py-2 text-sm outline-none"
          />
          <button
            type="submit"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
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
          class="h-48 animate-pulse rounded-xl bg-gray-200"
        ></div>
      </div>

      <div
        v-else-if="movers.length === 0"
        class="py-20 text-center text-gray-500"
      >
        <TruckIcon class="mx-auto mb-4 h-16 w-16 text-gray-300" />
        <p class="text-lg font-medium">No moving companies found</p>
        <p class="mt-1 text-sm">Try adjusting your search filters</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="mover in movers"
          :key="mover.id"
          :to="{ name: 'movers.show', params: { slug: mover.slug } }"
          class="group rounded-xl border bg-white p-6 shadow-sm transition hover:shadow-md"
        >
          <div class="flex items-start gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"
            >
              <TruckIcon class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <h3
                class="truncate font-semibold text-gray-900 group-hover:text-blue-600"
              >
                {{ mover.name }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ mover.city
                }}<span v-if="mover.province">, {{ mover.province }}</span>
              </p>
              <p
                v-if="mover.description"
                class="mt-2 line-clamp-2 text-sm text-gray-600"
              >
                {{ mover.description }}
              </p>
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <span class="text-sm font-medium text-blue-600"
              >View Details →</span
            >
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
