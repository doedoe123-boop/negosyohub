<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import {
  ChevronRightIcon,
  MinusIcon,
  PlusIcon,
  ShoppingCartIcon,
  BoltIcon,
  BuildingStorefrontIcon,
  StarIcon,
  CheckBadgeIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarSolid } from "@heroicons/vue/24/solid";
import { productsApi } from "@/api/products";
import { useCartStore } from "@/stores/cart";

const route = useRoute();
const cart = useCartStore();

const product = ref(null);
const loading = ref(true);
const selectedVariantId = ref(null);
const quantity = ref(1);
const selectedImage = ref(0);
const addedToCart = ref(false);

const selectedVariant = computed(
  () =>
    product.value?.variants?.find((v) => v.id === selectedVariantId.value) ??
    product.value?.variants?.[0],
);

const images = computed(() => {
  const imgs = product.value?.images ?? [];
  if (imgs.length === 0 && product.value?.thumbnail) return [product.value.thumbnail];
  return imgs;
});

const formattedPrice = computed(() => {
  const price = selectedVariant.value?.price;
  if (price == null) return null;
  return "₱" + parseFloat(price).toLocaleString("en-PH", { maximumFractionDigits: 2 });
});

const inStock = computed(() => {
  const stock = selectedVariant.value?.stock;
  return stock == null || stock > 0;
});

const stockLabel = computed(() => {
  const stock = selectedVariant.value?.stock;
  if (stock == null) return null;
  if (stock <= 0) return "Out of stock";
  if (stock <= 10) return `Only ${stock} left!`;
  return `${stock} pieces available`;
});

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
  await cart.addItem("product-variant", selectedVariantId.value, quantity.value);
  addedToCart.value = true;
  setTimeout(() => (addedToCart.value = false), 2500);
}
</script>

<template>
  <div>
    <!-- Skeleton -->
    <div v-if="loading" class="mx-auto max-w-6xl animate-pulse px-4 py-10 sm:px-6">
      <div class="grid gap-8 md:grid-cols-2">
        <div>
          <div class="aspect-square rounded-2xl bg-slate-200" />
          <div class="mt-3 flex gap-2">
            <div v-for="i in 4" :key="i" class="size-16 rounded-lg bg-slate-100" />
          </div>
        </div>
        <div class="space-y-4">
          <div class="h-6 w-2/3 rounded bg-slate-200" />
          <div class="h-5 w-1/4 rounded bg-slate-100" />
          <div class="h-12 w-1/3 rounded bg-slate-200" />
          <div class="h-4 w-full rounded bg-slate-100" />
          <div class="h-4 w-5/6 rounded bg-slate-100" />
          <div class="h-10 rounded-xl bg-slate-200" />
        </div>
      </div>
    </div>

    <template v-else-if="product">
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-1.5 text-xs text-slate-400">
          <RouterLink to="/" class="transition-colors hover:text-brand-600">Home</RouterLink>
          <ChevronRightIcon class="size-3" />
          <RouterLink
            v-if="product.store"
            :to="`/stores/${product.store.slug}`"
            class="transition-colors hover:text-brand-600"
          >
            {{ product.store.name }}
          </RouterLink>
          <RouterLink v-else to="/stores" class="transition-colors hover:text-brand-600">
            Stores
          </RouterLink>
          <ChevronRightIcon class="size-3" />
          <span class="line-clamp-1 text-slate-600">{{ product.name }}</span>
        </nav>

        <!-- Top section: gallery + info -->
        <div class="grid gap-8 md:grid-cols-2">
          <!-- ── Gallery ── -->
          <div>
            <!-- Main image -->
            <div class="relative aspect-square overflow-hidden rounded-2xl bg-slate-100">
              <img
                v-if="images[selectedImage]"
                :src="images[selectedImage]"
                :alt="product.name"
                class="h-full w-full object-cover transition-all duration-300"
              />
              <div v-else class="flex h-full items-center justify-center text-7xl">🛍️</div>
            </div>

            <!-- Thumbnail strip -->
            <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="(img, i) in images"
                :key="i"
                class="size-16 shrink-0 overflow-hidden rounded-lg border-2 transition-all"
                :class="
                  selectedImage === i
                    ? 'border-brand-500 opacity-100'
                    : 'border-transparent opacity-60 hover:opacity-90'
                "
                @click="selectedImage = i"
              >
                <img :src="img" class="h-full w-full object-cover" />
              </button>
            </div>
          </div>

          <!-- ── Product info ── -->
          <div class="flex flex-col">
            <!-- Category tag -->
            <div v-if="product.category" class="mb-2">
              <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-600">
                {{ product.category }}
              </span>
            </div>

            <!-- Title -->
            <h1 class="text-xl font-bold leading-snug text-slate-900 sm:text-2xl">
              {{ product.name }}
            </h1>

            <!-- Rating + sold -->
            <div v-if="product.rating_avg || product.sold_count" class="mt-2 flex items-center gap-3 text-sm text-slate-500">
              <div v-if="product.rating_avg" class="flex items-center gap-1">
                <span class="font-semibold text-amber-500">{{ product.rating_avg.toFixed(1) }}</span>
                <div class="flex">
                  <StarSolid
                    v-for="s in 5"
                    :key="s"
                    class="size-3.5"
                    :class="s <= Math.round(product.rating_avg) ? 'text-amber-400' : 'text-slate-200'"
                  />
                </div>
                <span v-if="product.reviews_count" class="text-xs">({{ product.reviews_count.toLocaleString() }})</span>
              </div>
              <span v-if="product.sold_count" class="text-xs">
                {{ product.sold_count.toLocaleString() }} sold
              </span>
            </div>

            <!-- Price band -->
            <div class="mt-4 rounded-xl bg-brand-50 px-4 py-3">
              <p v-if="formattedPrice" class="text-3xl font-bold text-brand-600">
                {{ formattedPrice }}
              </p>
              <p v-else class="text-lg font-medium text-slate-400">Price unavailable</p>

              <!-- Stock label -->
              <p v-if="stockLabel" class="mt-1 text-xs" :class="inStock ? 'text-slate-500' : 'text-red-500 font-medium'">
                {{ stockLabel }}
              </p>
            </div>

            <!-- Variant selector -->
            <div v-if="product.variants?.length > 1" class="mt-5">
              <p class="mb-2 text-sm font-semibold text-slate-700">
                Option
                <span v-if="selectedVariant" class="ml-1 font-normal text-slate-400">— {{ selectedVariant.name }}</span>
              </p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="variant in product.variants"
                  :key="variant.id"
                  type="button"
                  class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                  :class="
                    selectedVariantId === variant.id
                      ? 'border-brand-500 bg-brand-50 text-brand-600 shadow-sm'
                      : 'border-slate-200 text-slate-600 hover:border-brand-400'
                  "
                  @click="selectedVariantId = variant.id"
                >
                  {{ variant.name }}
                </button>
              </div>
            </div>

            <!-- Quantity -->
            <div class="mt-5 flex items-center gap-4">
              <span class="text-sm font-semibold text-slate-700">Quantity</span>
              <div class="flex items-center overflow-hidden rounded-xl border border-slate-200">
                <button
                  type="button"
                  class="flex size-9 items-center justify-center text-slate-600 transition-colors hover:bg-slate-50"
                  @click="quantity = Math.max(1, quantity - 1)"
                >
                  <MinusIcon class="size-4" />
                </button>
                <span class="w-12 text-center text-sm font-bold text-slate-800">{{ quantity }}</span>
                <button
                  type="button"
                  class="flex size-9 items-center justify-center text-slate-600 transition-colors hover:bg-slate-50"
                  @click="quantity++"
                >
                  <PlusIcon class="size-4" />
                </button>
              </div>
            </div>

            <!-- CTA buttons -->
            <div class="mt-6 grid grid-cols-2 gap-3">
              <button
                type="button"
                class="flex items-center justify-center gap-2 rounded-xl border-2 border-brand-500 py-3 text-sm font-semibold text-brand-600 transition-colors hover:bg-brand-50 disabled:opacity-50"
                :disabled="cart.loading || !selectedVariantId || !inStock"
                @click="addToCart"
              >
                <ShoppingCartIcon class="size-4" />
                {{ addedToCart ? "Added!" : "Add to Cart" }}
              </button>
              <button
                type="button"
                class="flex items-center justify-center gap-2 rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/20 transition-colors hover:bg-brand-600 disabled:opacity-50"
                :disabled="!selectedVariantId || !inStock"
              >
                <BoltIcon class="size-4" />
                Buy Now
              </button>
            </div>

            <p v-if="!inStock" class="mt-2 text-center text-xs text-red-500 font-medium">
              This variant is currently out of stock.
            </p>

            <!-- Divider -->
            <hr class="my-5 border-slate-100" />

            <!-- Store card -->
            <div v-if="product.store" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-3">
              <div class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                <img
                  v-if="product.store.logo"
                  :src="product.store.logo"
                  :alt="product.store.name"
                  class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full items-center justify-center">
                  <BuildingStorefrontIcon class="size-5 text-slate-400" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-800">{{ product.store.name }}</p>
                <div class="flex items-center gap-1">
                  <CheckBadgeIcon class="size-3.5 text-brand-500" />
                  <span class="text-xs text-slate-500">Official Store</span>
                </div>
              </div>
              <RouterLink
                :to="`/stores/${product.store.slug}`"
                class="shrink-0 rounded-lg border border-brand-500 px-3 py-1.5 text-xs font-semibold text-brand-600 transition-colors hover:bg-brand-50"
              >
                View Shop
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- ── Below fold ── -->
        <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_320px]">
          <!-- Description -->
          <div>
            <div class="rounded-2xl border border-slate-100 bg-white">
              <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-semibold text-slate-900">Product Description</h2>
              </div>
              <div class="px-6 py-5">
                <p
                  v-if="product.description"
                  class="whitespace-pre-line text-sm leading-relaxed text-slate-600"
                >
                  {{ product.description }}
                </p>
                <p v-else class="text-sm italic text-slate-400">No description provided.</p>
              </div>
            </div>

            <!-- Specs / attributes -->
            <div
              v-if="product.attributes && Object.keys(product.attributes).length"
              class="mt-6 rounded-2xl border border-slate-100 bg-white"
            >
              <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-semibold text-slate-900">Specifications</h2>
              </div>
              <div class="overflow-hidden">
                <table class="w-full text-sm">
                  <tbody class="divide-y divide-slate-50">
                    <tr
                      v-for="(value, key) in product.attributes"
                      :key="key"
                      class="odd:bg-white even:bg-slate-50/60"
                    >
                      <td class="w-1/3 px-6 py-3 font-medium capitalize text-slate-500">
                        {{ String(key).replace(/_/g, " ") }}
                      </td>
                      <td class="px-6 py-3 font-medium text-slate-800">{{ value }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Right sidebar: you may also like / same store products could go here -->
          <div>
            <div v-if="product.store" class="rounded-2xl border border-slate-100 bg-white p-5">
              <div class="mb-4 flex items-center gap-3">
                <div class="size-12 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                  <img
                    v-if="product.store.logo"
                    :src="product.store.logo"
                    :alt="product.store.name"
                    class="h-full w-full object-cover"
                  />
                  <div v-else class="flex h-full items-center justify-center">
                    <BuildingStorefrontIcon class="size-6 text-slate-400" />
                  </div>
                </div>
                <div>
                  <p class="font-bold text-slate-900">{{ product.store.name }}</p>
                  <p class="text-xs text-slate-500">Online Store</p>
                </div>
              </div>
              <RouterLink
                :to="`/stores/${product.store.slug}`"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-600"
              >
                <BuildingStorefrontIcon class="size-4" />
                Visit Store
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Not found -->
    <div v-else class="py-24 text-center text-slate-400">
      <p class="text-lg font-medium">Product not found.</p>
      <RouterLink to="/stores" class="mt-3 inline-block text-sm text-brand-500 hover:underline">Browse stores</RouterLink>
    </div>
  </div>
</template>
