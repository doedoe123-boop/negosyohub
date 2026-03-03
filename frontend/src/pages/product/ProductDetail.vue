<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import {
  ChevronRightIcon,
  MinusIcon,
  PlusIcon,
} from "@heroicons/vue/24/outline";
import { productsApi } from "@/api/products";
import { useCartStore } from "@/stores/cart";

const route = useRoute();
const cart = useCartStore();

const product = ref(null);
const loading = ref(true);
const selectedVariantId = ref(null);
const quantity = ref(1);

const selectedVariant = computed(
  () =>
    product.value?.variants?.find((v) => v.id === selectedVariantId.value) ??
    product.value?.variants?.[0],
);

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
    <!-- Skeleton -->
    <div v-if="loading" class="grid animate-pulse gap-8 md:grid-cols-2">
      <div class="aspect-square rounded-2xl bg-slate-200" />
      <div class="space-y-4">
        <div class="h-7 w-3/4 rounded bg-slate-200" />
        <div class="h-5 w-1/3 rounded bg-slate-100" />
        <div class="h-4 w-full rounded bg-slate-100" />
        <div class="h-4 w-5/6 rounded bg-slate-100" />
      </div>
    </div>

    <template v-else-if="product">
      <!-- Breadcrumb -->
      <nav class="mb-6 flex items-center gap-1.5 text-xs text-slate-400">
        <RouterLink to="/" class="hover:text-brand-600 transition-colors"
          >Home</RouterLink
        >
        <ChevronRightIcon class="size-3" />
        <RouterLink
          v-if="product.store"
          :to="`/stores/${product.store.slug}`"
          class="hover:text-brand-600 transition-colors"
        >
          {{ product.store.name }}
        </RouterLink>
        <RouterLink
          v-else
          to="/stores"
          class="hover:text-brand-600 transition-colors"
        >
          Stores
        </RouterLink>
        <ChevronRightIcon class="size-3" />
        <span class="line-clamp-1 text-slate-600">{{ product.name }}</span>
      </nav>

      <div class="grid gap-8 md:grid-cols-2">
        <!-- Gallery -->
        <div>
          <div class="aspect-square overflow-hidden rounded-2xl bg-slate-100">
            <img
              v-if="product.thumbnail"
              :src="product.thumbnail"
              :alt="product.name"
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="flex h-full items-center justify-center text-6xl"
            >
              🛍️
            </div>
          </div>
        </div>

        <!-- Details -->
        <div class="flex flex-col">
          <!-- Store attribution -->
          <RouterLink
            v-if="product.store"
            :to="`/stores/${product.store.slug}`"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600 transition-colors"
          >
            <img
              v-if="product.store.logo"
              :src="product.store.logo"
              :alt="product.store.name"
              class="size-5 rounded object-cover"
            />
            <span class="font-medium">{{ product.store.name }}</span>
          </RouterLink>

          <h1 class="text-2xl font-bold leading-tight text-slate-900">
            {{ product.name }}
          </h1>

          <p class="mt-3 text-2xl font-bold text-brand-600">
            {{
              selectedVariant?.price != null
                ? "₱" +
                  parseFloat(selectedVariant.price).toLocaleString("en-PH", {
                    maximumFractionDigits: 0,
                  })
                : "—"
            }}
          </p>

          <p
            v-if="product.description"
            class="mt-4 text-sm leading-relaxed text-slate-600"
          >
            {{ product.description }}
          </p>

          <!-- Variant selector -->
          <div v-if="product.variants?.length > 1" class="mt-5">
            <p class="mb-2 text-sm font-medium text-slate-700">Option</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="variant in product.variants"
                :key="variant.id"
                type="button"
                class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"
                :class="
                  selectedVariantId === variant.id
                    ? 'border-brand-500 bg-brand-50 text-brand-600'
                    : 'border-slate-300 text-slate-600 hover:border-brand-400'
                "
                @click="selectedVariantId = variant.id"
              >
                {{ variant.name }}
              </button>
            </div>
          </div>

          <!-- Quantity -->
          <div class="mt-5 flex items-center gap-3">
            <span class="text-sm font-medium text-slate-700">Quantity</span>
            <div
              class="flex items-center overflow-hidden rounded-xl border border-slate-300"
            >
              <button
                type="button"
                class="flex size-9 items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors"
                @click="quantity = Math.max(1, quantity - 1)"
              >
                <MinusIcon class="size-4" />
              </button>
              <span
                class="w-10 text-center text-sm font-semibold text-slate-800"
              >
                {{ quantity }}
              </span>
              <button
                type="button"
                class="flex size-9 items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors"
                @click="quantity++"
              >
                <PlusIcon class="size-4" />
              </button>
            </div>
          </div>

          <!-- CTA -->
          <button
            type="button"
            class="mt-6 w-full rounded-xl bg-brand-500 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/20 hover:bg-brand-600 transition-colors disabled:opacity-50"
            :disabled="cart.loading || !selectedVariantId"
            @click="addToCart"
          >
            {{ cart.loading ? "Adding…" : "Add to Cart" }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
