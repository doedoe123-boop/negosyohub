<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { productsApi } from "@/api/products";
import { useCartStore } from "@/stores/cart";

const route = useRoute();
const cart = useCartStore();

const product = ref(null);
const loading = ref(true);
const selectedVariantId = ref(null);
const quantity = ref(1);

onMounted(async () => {
  try {
    const { data } = await productsApi.show(route.params.id);
    product.value = data;
    selectedVariantId.value = data.variants?.[0]?.id ?? null;
  } finally {
    loading.value = false;
  }
});

async function addToCart() {
  if (!selectedVariantId.value) return;
  await cart.addItem(
    "product-variant",
    selectedVariantId.value,
    quantity.value,
  );
}
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <div v-if="loading" class="grid gap-8 md:grid-cols-2">
      <div class="aspect-square animate-pulse rounded-2xl bg-gray-100" />
      <div class="space-y-4">
        <div class="h-6 w-3/4 animate-pulse rounded bg-gray-100" />
        <div class="h-4 w-1/2 animate-pulse rounded bg-gray-100" />
      </div>
    </div>

    <div v-else-if="product" class="grid gap-8 md:grid-cols-2">
      <!-- Gallery -->
      <div>
        <img
          :src="product.thumbnail ?? '/placeholder.png'"
          :alt="product.name"
          class="aspect-square w-full rounded-2xl object-cover bg-gray-100"
        />
      </div>

      <!-- Info -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ product.name }}</h1>
        <p class="mt-2 text-2xl font-semibold text-brand-600">
          {{ product.variants?.[0]?.price?.formatted ?? "—" }}
        </p>
        <p class="mt-4 text-sm leading-relaxed text-gray-600">
          {{ product.description }}
        </p>

        <!-- Quantity -->
        <div class="mt-6 flex items-center gap-3">
          <label class="text-sm font-medium text-gray-700">Qty</label>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="size-8 rounded border text-center hover:bg-gray-100"
              @click="quantity = Math.max(1, quantity - 1)"
            >
              −
            </button>
            <span class="w-8 text-center text-sm font-medium">{{
              quantity
            }}</span>
            <button
              type="button"
              class="size-8 rounded border text-center hover:bg-gray-100"
              @click="quantity++"
            >
              +
            </button>
          </div>
        </div>

        <button
          type="button"
          class="mt-6 w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors disabled:opacity-50"
          :disabled="cart.loading"
          @click="addToCart"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>
