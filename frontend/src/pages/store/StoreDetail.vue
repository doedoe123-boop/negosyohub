<script setup>
import { ref, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { ChevronRightIcon, MapPinIcon } from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";
import { useCartStore } from "@/stores/cart";

const sectorLabels = {
  ecommerce: "E-Commerce",
  real_estate: "Real Estate",
  services: "Services",
};

// Gradient per sector for the banner
const sectorGradient = {
  ecommerce: "from-brand-600 via-brand-700 to-brand-800",
  real_estate: "from-teal-600 via-teal-700 to-teal-800",
  services: "from-slate-700 via-slate-800 to-slate-900",
};

const route = useRoute();
const cart = useCartStore();

const store = ref(null);
const products = ref([]);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const [storeRes, prodRes] = await Promise.all([
      storesApi.show(route.params.slug),
      storesApi.products(route.params.slug),
    ]);
    store.value = storeRes.data;
    products.value = prodRes.data?.data ?? prodRes.data;
  } catch (e) {
    error.value =
      e.response?.status === 404 ? "Store not found." : "Failed to load store.";
  } finally {
    loading.value = false;
  }
});

async function addToCart(product) {
  await cart.addItem("product-variant", product.default_variant_id, 1, {
    store_id: store.value.id,
  });
}
</script>

<template>
  <div>
    <!-- Skeleton -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-52 w-full bg-slate-200" />
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="h-7 w-56 rounded bg-slate-200" />
        <div class="mt-3 h-4 w-40 rounded bg-slate-100" />
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="mx-auto max-w-7xl px-4 py-20 text-center text-slate-500 sm:px-6"
    >
      {{ error }}
    </div>

    <template v-else>
      <!-- Banner -->
      <div class="relative h-48 w-full overflow-hidden sm:h-56">
        <img
          v-if="store.banner"
          :src="store.banner"
          :alt="store.name"
          class="h-full w-full object-cover"
        />
        <div
          v-else
          class="h-full w-full bg-gradient-to-br"
          :class="sectorGradient[store.sector] ?? 'from-slate-700 to-slate-900'"
        />
        <!-- Subtle overlay for text contrast -->
        <div class="absolute inset-0 bg-black/20" />
      </div>

      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <!-- Store info bar (overlaps banner) -->
        <div class="relative -mt-10 mb-6 flex items-end gap-4">
          <div
            v-if="store.logo"
            class="size-20 shrink-0 overflow-hidden rounded-2xl bg-white ring-4 ring-white shadow-md"
          >
            <img
              :src="store.logo"
              :alt="store.name"
              class="h-full w-full object-cover"
            />
          </div>
          <div
            v-else
            class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-white text-3xl ring-4 ring-white shadow-md"
          >
            🏪
          </div>
          <div class="mb-1 min-w-0">
            <h1 class="text-2xl font-bold leading-tight text-slate-900">
              {{ store.name }}
            </h1>
            <div
              class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500"
            >
              <span
                v-if="store.sector"
                class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600"
              >
                {{ sectorLabels[store.sector] ?? store.sector }}
              </span>
              <span v-if="store.address?.city" class="flex items-center gap-1">
                <MapPinIcon class="size-3.5" />
                {{ store.address.city }}
              </span>
            </div>
          </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="mb-5 flex items-center gap-1.5 text-xs text-slate-400">
          <RouterLink to="/" class="hover:text-brand-600 transition-colors"
            >Home</RouterLink
          >
          <ChevronRightIcon class="size-3" />
          <RouterLink
            to="/stores"
            class="hover:text-brand-600 transition-colors"
            >Stores</RouterLink
          >
          <ChevronRightIcon class="size-3" />
          <span class="text-slate-600">{{ store.name }}</span>
        </nav>

        <!-- Description -->
        <p
          v-if="store.description"
          class="mb-8 max-w-2xl text-sm leading-relaxed text-slate-600"
        >
          {{ store.description }}
        </p>

        <!-- Products -->
        <h2 class="mb-4 text-lg font-bold text-slate-900">Products</h2>

        <div
          v-if="products.length === 0"
          class="rounded-2xl border border-dashed border-slate-300 py-12 text-center text-slate-400"
        >
          <p class="text-sm">No products available yet.</p>
        </div>

        <div
          v-else
          class="grid grid-cols-2 gap-4 pb-16 sm:grid-cols-3 lg:grid-cols-4"
        >
          <div
            v-for="product in products"
            :key="product.id"
            class="group overflow-hidden rounded-2xl border bg-white shadow-sm hover:shadow-md transition-shadow"
          >
            <RouterLink :to="`/products/${product.id}`">
              <div class="aspect-square w-full overflow-hidden bg-slate-100">
                <img
                  v-if="product.thumbnail"
                  :src="product.thumbnail"
                  :alt="product.name"
                  class="h-full w-full object-cover transition-transform group-hover:scale-105"
                />
                <div
                  v-else
                  class="flex h-full items-center justify-center text-4xl"
                >
                  🛍️
                </div>
              </div>
            </RouterLink>
            <div class="p-3">
              <RouterLink :to="`/products/${product.id}`">
                <p
                  class="line-clamp-2 text-sm font-medium text-slate-800 group-hover:text-brand-600 transition-colors"
                >
                  {{ product.name }}
                </p>
              </RouterLink>
              <p class="mt-1 text-sm font-semibold text-brand-600">
                {{
                  product.price != null
                    ? "₱" +
                      parseFloat(product.price).toLocaleString("en-PH", {
                        maximumFractionDigits: 0,
                      })
                    : "—"
                }}
              </p>
              <button
                type="button"
                class="mt-2 w-full rounded-lg bg-brand-500 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition-colors disabled:opacity-50"
                :disabled="cart.loading"
                @click="addToCart(product)"
              >
                Add to Cart
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
