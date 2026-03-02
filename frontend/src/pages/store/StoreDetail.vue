<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { storesApi } from "@/api/stores";
import { useCartStore } from "@/stores/cart";

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
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <div class="h-8 w-48 animate-pulse rounded bg-gray-100" />
      <div class="h-4 w-64 animate-pulse rounded bg-gray-100" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="py-16 text-center text-red-500">
      {{ error }}
    </div>

    <template v-else>
      <!-- Store header -->
      <div class="mb-8 flex items-center gap-4">
        <img
          :src="store.logo_url ?? '/placeholder.png'"
          :alt="store.name"
          class="size-20 rounded-2xl object-cover bg-gray-100"
        />
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ store.name }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ store.sector }} · {{ store.city }}
          </p>
          <p class="mt-2 text-sm text-gray-600">{{ store.description }}</p>
        </div>
      </div>

      <!-- Products grid -->
      <h2 class="mb-4 text-xl font-semibold text-gray-800">Products</h2>

      <div v-if="products.length === 0" class="py-8 text-gray-400">
        No products available yet.
      </div>

      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <div
          v-for="product in products"
          :key="product.id"
          class="rounded-2xl border bg-white shadow-sm overflow-hidden"
        >
          <img
            :src="product.thumbnail ?? '/placeholder.png'"
            :alt="product.name"
            class="aspect-square w-full object-cover bg-gray-100"
          />
          <div class="p-3">
            <p class="font-medium text-gray-800 line-clamp-2 text-sm">
              {{ product.name }}
            </p>
            <p class="mt-1 font-semibold text-brand-600">
              {{ product.price?.formatted ?? "—" }}
            </p>
            <button
              type="button"
              class="mt-2 w-full rounded-lg bg-brand-500 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition-colors"
              @click="addToCart(product)"
              :disabled="cart.loading"
            >
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
