<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  BuildingStorefrontIcon,
  HomeModernIcon,
  BriefcaseIcon,
} from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";
import { productsApi } from "@/api/products";
import { propertiesApi } from "@/api/properties";
import PhMapHero from "@/components/PhMapHero.vue";

const featuredStores = ref([]);
const featuredProducts = ref([]);
const latestProperties = ref([]);

const sectorLabels = {
  ecommerce: "E-Commerce",
  real_estate: "Real Estate",
  services: "Services",
};
const loading = ref(true);
const productsLoading = ref(true);
const propertiesLoading = ref(true);

const sectors = [
  {
    label: "E-Commerce",
    description: "Shop from local online stores & retailers",
    to: "/stores",
    icon: BuildingStorefrontIcon,
    color: "bg-brand-50 text-brand-600 ring-brand-200",
    badge: "bg-brand-100 text-brand-700",
  },
  {
    label: "Real Estate",
    description: "Houses, condos, and commercial spaces",
    to: "/properties",
    icon: HomeModernIcon,
    color: "bg-teal-50 text-teal-600 ring-teal-200",
    badge: "bg-teal-100 text-teal-700",
  },
  {
    label: "Services",
    description: "Coming soon — freelancers and professionals",
    to: null,
    icon: BriefcaseIcon,
    color: "bg-slate-50 text-slate-400 ring-slate-200",
    badge: "bg-slate-100 text-slate-500",
    soon: true,
  },
];

onMounted(async () => {
  try {
    const { data } = await storesApi.list({ per_page: 8, featured: true });
    featuredStores.value = data.data ?? data;
  } catch {
    featuredStores.value = [];
  } finally {
    loading.value = false;
  }

  try {
    const { data } = await productsApi.list({ per_page: 6 });
    featuredProducts.value = data.data ?? data;
  } catch {
    featuredProducts.value = [];
  } finally {
    productsLoading.value = false;
  }

  try {
    const { data } = await propertiesApi.list({ per_page: 4 });
    latestProperties.value = data.data ?? data;
  } catch {
    latestProperties.value = [];
  } finally {
    propertiesLoading.value = false;
  }
});
</script>

<template>
  <div>
    <!-- Hero -->
    <PhMapHero />

    <!-- Sector picker -->
    <section class="border-b border-slate-100 bg-white px-4 py-10 sm:px-6">
      <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex items-end justify-between">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Browse by Sector</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              Choose what you're looking for.
            </p>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <component
            :is="sector.soon ? 'div' : RouterLink"
            v-for="sector in sectors"
            :key="sector.label"
            :to="sector.soon ? undefined : sector.to"
            class="group flex items-start gap-4 rounded-2xl border bg-white p-5 shadow-sm transition-all"
            :class="
              sector.soon
                ? 'cursor-not-allowed opacity-50'
                : 'cursor-pointer hover:shadow-md hover:-translate-y-0.5'
            "
          >
            <span
              class="flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform group-hover:scale-105"
              :class="sector.color"
            >
              <component :is="sector.icon" class="size-6" />
            </span>
            <div class="min-w-0">
              <p class="flex items-center gap-2 font-semibold text-slate-800">
                {{ sector.label }}
                <span
                  v-if="sector.soon"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="sector.badge"
                  >Coming Soon</span
                >
              </p>
              <p class="mt-0.5 line-clamp-2 text-sm text-slate-500">
                {{ sector.description }}
              </p>
            </div>
          </component>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="mx-auto max-w-7xl px-4 pt-10 pb-8 sm:px-6">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Latest Products</h2>
          <p class="mt-1 text-sm text-slate-500">
            Shop from local e-commerce stores.
          </p>
        </div>
        <RouterLink
          to="/stores"
          class="text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors"
        >
          Browse stores →
        </RouterLink>
      </div>

      <!-- Skeleton -->
      <div
        v-if="productsLoading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="h-40 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>

      <!-- Empty -->
      <div
        v-else-if="featuredProducts.length === 0"
        class="rounded-2xl border border-dashed border-slate-300 py-10 text-center text-slate-400 text-sm"
      >
        No products yet — check back soon!
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <RouterLink
          v-for="product in featuredProducts"
          :key="product.id"
          :to="`/products/${product.id}`"
          class="group flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="aspect-square overflow-hidden bg-slate-100">
            <img
              v-if="product.thumbnail"
              :src="product.thumbnail"
              :alt="product.name"
              class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full items-center justify-center text-3xl"
            >
              🛍️
            </div>
          </div>
          <div class="p-2">
            <p
              class="line-clamp-2 text-xs font-medium text-slate-700 group-hover:text-brand-600 transition-colors"
            >
              {{ product.name }}
            </p>
            <p
              v-if="product.price"
              class="mt-1 text-xs font-bold text-brand-600"
            >
              ₱{{ product.price.toLocaleString() }}
            </p>
          </div>
        </RouterLink>
      </div>
    </section>

    <!-- Latest Properties -->
    <section class="bg-teal-50 py-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="mb-6 flex items-end justify-between">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Latest Properties</h2>
            <p class="mt-1 text-sm text-slate-500">
              Houses, condos, and commercial spaces for sale or rent.
            </p>
          </div>
          <RouterLink
            to="/properties"
            class="text-sm font-medium text-teal-700 hover:text-teal-800 transition-colors"
          >
            View all →
          </RouterLink>
        </div>

        <!-- Skeleton -->
        <div
          v-if="propertiesLoading"
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
          <div
            v-for="i in 4"
            :key="i"
            class="h-56 animate-pulse rounded-2xl bg-teal-100"
          />
        </div>

        <!-- Empty -->
        <div
          v-else-if="latestProperties.length === 0"
          class="rounded-2xl border border-dashed border-teal-300 py-10 text-center text-slate-400 text-sm"
        >
          No listings yet — check back soon!
        </div>

        <!-- Grid -->
        <div
          v-else
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
          <RouterLink
            v-for="property in latestProperties"
            :key="property.id"
            :to="`/properties/${property.slug}`"
            class="group overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5"
          >
            <div class="relative aspect-[16/9] overflow-hidden bg-slate-100">
              <img
                v-if="property.images && property.images[0]"
                :src="property.images[0]"
                :alt="property.title"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
              />
              <div
                v-else
                class="flex h-full items-center justify-center bg-gradient-to-br from-teal-50 to-slate-100 text-3xl"
              >
                🏡
              </div>
              <span
                class="absolute left-2 top-2 rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="{
                  'bg-emerald-100 text-emerald-700':
                    property.listing_type === 'for_sale',
                  'bg-sky-100 text-sky-700':
                    property.listing_type === 'for_rent',
                  'bg-amber-100 text-amber-700':
                    property.listing_type === 'for_lease',
                  'bg-purple-100 text-purple-700':
                    property.listing_type === 'pre_selling',
                  'bg-slate-100 text-slate-600': ![
                    'for_sale',
                    'for_rent',
                    'for_lease',
                    'pre_selling',
                  ].includes(property.listing_type),
                }"
              >
                {{
                  {
                    for_sale: "For Sale",
                    for_rent: "For Rent",
                    for_lease: "For Lease",
                    pre_selling: "Pre-Selling",
                  }[property.listing_type] ?? property.listing_type
                }}
              </span>
            </div>
            <div class="p-3">
              <p
                class="line-clamp-2 text-sm font-semibold text-slate-800 group-hover:text-teal-700 transition-colors"
              >
                {{ property.title }}
              </p>
              <p class="mt-0.5 text-xs text-slate-400">{{ property.city }}</p>
              <p class="mt-1.5 text-sm font-bold text-teal-700">
                ₱{{
                  parseFloat(property.price).toLocaleString("en-PH", {
                    maximumFractionDigits: 0,
                  })
                }}
              </p>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Featured stores -->
    <section class="mx-auto max-w-7xl px-4 pt-4 pb-16 sm:px-6">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Featured Stores</h2>
          <p class="mt-1 text-sm text-slate-500">
            Handpicked local businesses.
          </p>
        </div>
        <RouterLink
          to="/stores"
          class="text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors"
        >
          View all →
        </RouterLink>
      </div>

      <!-- Skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
      >
        <div
          v-for="i in 8"
          :key="i"
          class="h-44 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>

      <!-- Empty -->
      <div
        v-else-if="featuredStores.length === 0"
        class="rounded-2xl border border-dashed border-slate-300 py-16 text-center text-slate-400"
      >
        No featured stores yet — check back soon!
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <RouterLink
          v-for="store in featuredStores"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="group flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5"
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
              class="flex h-full items-center justify-center bg-gradient-to-br from-brand-50 to-brand-100"
            >
              <span class="text-3xl">🏪</span>
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
                {{ sectorLabels[store.sector] ?? store.sector }}
              </p>
            </div>
          </div>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
