<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import {
  ChevronRightIcon,
  MinusIcon,
  PlusIcon,
  ShoppingCartIcon,
  BoltIcon,
  BuildingStorefrontIcon,
  StarIcon,
  CheckBadgeIcon,
  MagnifyingGlassPlusIcon,
  ChatBubbleLeftRightIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarSolid } from "@heroicons/vue/24/solid";
import { productsApi } from "@/api/products";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { reviewsApi } from "@/api/reviews";
import { useCartStore } from "@/stores/cart";
import PhotoLightbox from "@/components/PhotoLightbox.vue";
import ReviewForm from "@/components/ReviewForm.vue";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const cart = useCartStore();

const product = ref(null);
const loading = ref(true);
const selectedVariantId = ref(null);
const quantity = ref(1);
const selectedImage = ref(0);
const addedToCart = ref(false);
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

function openLightbox(index = 0) {
  lightboxIndex.value = index;
  lightboxOpen.value = true;
}

const activeTab = ref("specs");

const selectedVariant = computed(
  () =>
    product.value?.variants?.find((v) => v.id === selectedVariantId.value) ??
    product.value?.variants?.[0],
);

const images = computed(() => {
  const imgs = product.value?.images ?? [];
  if (imgs.length === 0 && product.value?.thumbnail)
    return [product.value.thumbnail];
  return imgs;
});

const formattedPrice = computed(() => {
  const price = selectedVariant.value?.price;
  if (price == null) return null;
  return (
    "₱" +
    parseFloat(price).toLocaleString("en-PH", { maximumFractionDigits: 2 })
  );
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

    useSeoMeta({
      title: data.name,
      description: data.description || null,
      ogImage: data.thumbnail || data.images?.[0] || null,
      ogType: "product",
    });
  } finally {
    loading.value = false;
  }
});

function requireAuth() {
  if (!auth.isLoggedIn) {
    router.push({ name: "auth.login", query: { redirect: route.fullPath } });
    return false;
  }
  return true;
}

async function addToCart() {
  if (!requireAuth()) return;
  if (!selectedVariantId.value) return;
  await cart.addItem(
    "product-variant",
    selectedVariantId.value,
    quantity.value,
  );
  addedToCart.value = true;
  setTimeout(() => (addedToCart.value = false), 2500);
}

async function buyNow() {
  if (!requireAuth()) return;
  if (!selectedVariantId.value || !inStock.value) return;
  await cart.addItem(
    "product-variant",
    selectedVariantId.value,
    quantity.value,
  );
  cart.closeDrawer();
  router.push({ name: "checkout.index" });
}

const reviewFormRef = ref(null);

async function submitProductReview(payload) {
  try {
    await reviewsApi.submitForProduct(product.value.id, payload);
    reviewFormRef.value?.onSuccess();
  } catch (e) {
    reviewFormRef.value?.onError(
      e.response?.data?.message ?? "Failed to submit review. Please try again.",
    );
  }
}
</script>

<template>
  <div>
    <!-- Skeleton -->
    <div
      v-if="loading"
      class="mx-auto max-w-6xl animate-pulse px-4 py-10 sm:px-6"
    >
      <div class="grid gap-8 md:grid-cols-2">
        <div>
          <div class="aspect-square rounded-2xl bg-slate-200" />
          <div class="mt-3 flex gap-2">
            <div
              v-for="i in 4"
              :key="i"
              class="size-16 rounded-lg bg-slate-100"
            />
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
          <RouterLink to="/" class="transition-colors hover:text-brand-600"
            >Home</RouterLink
          >
          <ChevronRightIcon class="size-3" />
          <RouterLink
            v-if="product.store"
            :to="`/stores/${product.store.slug}`"
            class="transition-colors hover:text-brand-600"
          >
            {{ product.store.name }}
          </RouterLink>
          <RouterLink
            v-else
            to="/stores"
            class="transition-colors hover:text-brand-600"
          >
            Stores
          </RouterLink>
          <ChevronRightIcon class="size-3" />
          <span class="line-clamp-1 text-slate-600">{{ product.name }}</span>
        </nav>

        <!-- Top section: gallery + info -->
        <div class="grid gap-8 lg:grid-cols-[60%_40%]">
          <!-- ── Gallery ── -->
          <div>
            <!-- Main image -->
            <div
              class="relative aspect-square overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-sm cursor-pointer group"
              @click="images[selectedImage] && openLightbox(selectedImage)"
            >
              <img
                v-if="images[selectedImage]"
                :src="images[selectedImage]"
                :alt="product.name"
                class="h-full w-full object-cover transition-all duration-500 group-hover:scale-105"
              />
              <div
                v-else
                class="flex h-full items-center justify-center text-7xl bg-slate-50"
              >
                🛍️
              </div>

              <!-- Zoom hint overlay -->
              <div
                v-if="images[selectedImage]"
                class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors"
              >
                <div
                  class="rounded-full bg-white/90 p-3 shadow-lg text-slate-700 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm"
                >
                  <MagnifyingGlassPlusIcon class="size-6" />
                </div>
              </div>
            </div>

            <!-- Thumbnail strip -->
            <div
              v-if="images.length > 1"
              class="mt-4 flex gap-3 overflow-x-auto pb-2 snap-x"
            >
              <button
                v-for="(img, i) in images"
                :key="i"
                class="size-20 shrink-0 snap-start overflow-hidden rounded-xl border-2 transition-all bg-white"
                :class="
                  selectedImage === i
                    ? 'border-brand-500 ring-2 ring-brand-500/20'
                    : 'border-slate-200 opacity-70 hover:opacity-100 hover:border-slate-300'
                "
                @click="
                  selectedImage = i;
                  openLightbox(i);
                "
              >
                <img :src="img" class="h-full w-full object-cover" />
              </button>
            </div>
          </div>

          <!-- ── Product info ── -->
          <div class="flex flex-col">
            <!-- Category tag -->
            <div v-if="product.category" class="mb-2">
              <span
                class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-600"
              >
                {{ product.category }}
              </span>
            </div>

            <div class="mb-4">
              <!-- Title -->
              <h1
                class="text-2xl font-extrabold leading-tight tracking-tight text-[#0F2044] sm:text-3xl"
              >
                {{ product.name }}
              </h1>

              <!-- Rating + sold -->
              <div class="mt-3 flex items-center gap-3 text-sm text-slate-500">
                <div
                  class="flex items-center gap-1.5"
                  v-if="product.average_rating"
                >
                  <div class="flex text-amber-400">
                    <StarSolid class="size-4" />
                    <StarSolid class="size-4" />
                    <StarSolid class="size-4" />
                    <StarSolid class="size-4" />
                    <StarSolid
                      class="size-4"
                      v-if="product.average_rating >= 4.5"
                    />
                    <StarIcon class="size-4" v-else />
                  </div>
                  <span class="font-semibold text-slate-700">{{
                    product.average_rating.toFixed(1)
                  }}</span>
                  <span
                    class="text-xs hover:underline cursor-pointer"
                    @click="activeTab = 'reviews'"
                    >({{ product.review_count }} reviews)</span
                  >
                </div>
                <div class="flex items-center gap-1.5" v-else>
                  <div class="flex text-slate-300">
                    <StarIcon class="size-4" v-for="i in 5" :key="i" />
                  </div>
                  <span class="text-xs text-slate-400">No reviews</span>
                </div>
              </div>
            </div>

            <!-- Price band -->
            <div class="mt-1">
              <div class="flex items-baseline gap-2">
                <span
                  class="text-3xl font-extrabold text-[#F95D2F]"
                  v-if="formattedPrice"
                  >{{ formattedPrice }}</span
                >
                <span
                  class="text-lg font-medium text-slate-400 line-through"
                  v-if="formattedPrice && selectedVariant?.price"
                  >₱{{
                    (parseFloat(selectedVariant.price) * 1.2).toLocaleString(
                      "en-PH",
                      { maximumFractionDigits: 2 },
                    )
                  }}</span
                >
                <span
                  v-if="!formattedPrice"
                  class="text-lg font-medium text-slate-400"
                  >Price unavailable</span
                >
              </div>
            </div>

            <!-- Social Proof Flags -->
            <div class="mt-5 flex flex-col gap-2.5">
              <div
                v-if="product.sold_count && product.sold_count > 0"
                class="inline-flex w-fit items-center gap-2 rounded-lg border border-orange-100 bg-orange-50 px-3 py-2 text-sm text-slate-700 shadow-sm"
              >
                <span class="text-lg">🔥</span>
                <span class="font-medium blur-none"
                  >{{ product.sold_count }} people bought this</span
                >
              </div>

              <div
                v-if="
                  selectedVariant?.stock != null &&
                  selectedVariant.stock < 10 &&
                  selectedVariant.stock > 0
                "
                class="inline-flex w-fit items-center gap-2 text-sm font-semibold text-[#F95D2F]"
              >
                <svg
                  class="size-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
                <span>Low Stock: Only {{ selectedVariant.stock }} left!</span>
              </div>
              <div
                v-else-if="selectedVariant?.stock === 0"
                class="inline-flex w-fit items-center gap-2 text-sm font-semibold text-red-600"
              >
                Out of Stock
              </div>
            </div>

            <!-- Variant selector -->
            <div v-if="product.variants?.length > 1" class="mt-5">
              <p class="mb-2 text-sm font-semibold text-slate-700">
                Option
                <span
                  v-if="selectedVariant"
                  class="ml-1 font-normal text-slate-400"
                  >— {{ selectedVariant.name }}</span
                >
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
              <div
                class="flex items-center overflow-hidden rounded-xl border border-slate-200"
              >
                <button
                  type="button"
                  class="flex size-9 items-center justify-center text-slate-600 transition-colors hover:bg-slate-50"
                  @click="quantity = Math.max(1, quantity - 1)"
                >
                  <MinusIcon class="size-4" />
                </button>
                <span
                  class="w-12 text-center text-sm font-bold text-slate-800"
                  >{{ quantity }}</span
                >
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
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
              <button
                type="button"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-bold text-white shadow-sm hover:from-brand-600 hover:to-brand-700 hover:shadow-brand-500/30 hover:shadow-md active:scale-[0.98] disabled:opacity-50 transition-all"
                :disabled="cart.loading || !selectedVariantId || !inStock"
                @click="addToCart"
              >
                <ShoppingCartIcon class="size-4.5" />
                {{ addedToCart ? "✓ Added to Cart" : "Add to Cart" }}
              </button>
              <button
                type="button"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-brand-500 py-3.5 text-sm font-bold text-brand-600 hover:bg-brand-50 active:scale-[0.98] disabled:opacity-50 transition-all"
                :disabled="!selectedVariantId || !inStock"
                @click="buyNow"
              >
                <BoltIcon class="size-4.5" />
                Buy Now
              </button>
            </div>

            <p
              v-if="!inStock"
              class="mt-2 text-center text-xs text-red-500 font-medium"
            >
              This variant is currently out of stock.
            </p>

            <!-- Divider -->
            <hr class="my-5 border-slate-100" />

            <!-- Store card -->
            <div
              v-if="product.store"
              class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-3"
            >
              <div
                class="size-11 shrink-0 overflow-hidden rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold shadow-sm"
              >
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
                <p class="truncate text-sm font-bold text-[#0F2044]">
                  {{ product.store.name }}
                </p>
                <div class="mt-0.5 flex items-center gap-1">
                  <CheckBadgeIcon class="size-3.5 text-[#059669]" />
                  <span
                    class="text-[10px] font-bold uppercase tracking-wider text-[#059669]"
                    >Verified Seller</span
                  >
                </div>
              </div>
              <RouterLink
                :to="`/stores/${product.store.slug}`"
                class="shrink-0 rounded-full border border-[#059669] px-4 py-1.5 text-xs font-bold text-[#059669] transition-colors hover:bg-emerald-50"
              >
                Visit Store
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- ── Main Section Below: Tabs ── -->
        <div
          class="mt-8 rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden"
        >
          <div class="flex border-b border-slate-100 overflow-x-auto">
            <button
              @click="activeTab = 'specs'"
              class="flex-1 whitespace-nowrap px-6 py-4 text-sm font-bold transition-colors"
              :class="
                activeTab === 'specs'
                  ? 'border-b-2 border-brand-500 text-brand-600'
                  : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'
              "
            >
              Detailed Specs
            </button>
            <button
              @click="activeTab = 'reviews'"
              class="flex-1 whitespace-nowrap px-6 py-4 text-sm font-bold transition-colors"
              :class="
                activeTab === 'reviews'
                  ? 'border-b-2 border-brand-500 text-brand-600'
                  : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'
              "
            >
              Reviews
            </button>
            <button
              @click="activeTab = 'shipping'"
              class="flex-1 whitespace-nowrap px-6 py-4 text-sm font-bold transition-colors"
              :class="
                activeTab === 'shipping'
                  ? 'border-b-2 border-brand-500 text-brand-600'
                  : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'
              "
            >
              Shipping Info
            </button>
          </div>

          <div class="p-6 md:p-8">
            <!-- Specs Tab -->
            <div v-show="activeTab === 'specs'">
              <p
                v-if="product.description"
                class="whitespace-pre-line text-sm leading-relaxed text-slate-600 mb-8 max-w-3xl"
              >
                {{ product.description }}
              </p>

              <div
                v-if="
                  product.attributes && Object.keys(product.attributes).length
                "
                class="max-w-2xl"
              >
                <h3 class="font-bold text-[#0F2044] mb-4 text-lg">
                  Specifications
                </h3>
                <ul class="space-y-3">
                  <li
                    v-for="(value, key) in product.attributes"
                    :key="key"
                    class="flex justify-between text-sm py-2 border-b border-slate-50 last:border-0"
                  >
                    <span class="text-slate-500 capitalize">{{
                      String(key).replace(/_/g, " ")
                    }}</span>
                    <span class="font-medium text-[#0F2044] text-right">{{
                      value
                    }}</span>
                  </li>
                </ul>
              </div>
              <div v-else class="text-sm italic text-slate-400">
                No detailed specifications provided.
              </div>
            </div>

            <!-- Reviews Tab -->
            <div v-show="activeTab === 'reviews'" class="max-w-4xl">
              <ReviewForm
                ref="reviewFormRef"
                :review-count="product.review_count ?? 0"
                :average-rating="product.average_rating"
                :reviews="product.reviews ?? []"
                item-label="product"
                @submit="submitProductReview"
              />
            </div>

            <!-- Shipping Tab -->
            <div v-show="activeTab === 'shipping'" class="max-w-2xl">
              <h3 class="font-bold text-[#0F2044] mb-4 text-lg">
                Delivery Options
              </h3>
              <ul class="space-y-4 text-sm text-slate-600">
                <li class="flex gap-3">
                  <div class="mt-0.5 text-slate-400">
                    <svg
                      class="size-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="font-bold text-slate-800">Standard Delivery</p>
                    <p>
                      Get it delivered to
                      <span class="font-medium text-slate-700">{{
                        product.store?.city || "Nationwide"
                      }}</span>
                      via NegosyoHub Express.
                    </p>
                    <p class="mt-1 font-semibold text-[#F95D2F]">
                      Calculated at checkout
                    </p>
                  </div>
                </li>
                <li class="flex gap-3">
                  <div class="mt-0.5 text-slate-400">
                    <svg
                      class="size-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="font-bold text-slate-800">Store Pickup</p>
                    <p>
                      Pick up straight from the
                      {{ product.store?.name || "Seller" }} storefront.
                    </p>
                    <p class="mt-1 font-semibold text-emerald-600">Free</p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Sticky Bottom Bar (Mobile) -->
    <div
      v-if="product"
      class="fixed bottom-0 left-0 right-0 z-50 flex gap-3 border-t border-slate-200 bg-white p-4 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] md:hidden"
    >
      <button
        class="flex flex-col items-center justify-center px-4 text-slate-500 transition-colors hover:text-brand-600"
      >
        <ChatBubbleLeftRightIcon class="size-6" />
        <span class="text-[10px] font-bold mt-1">Chat</span>
      </button>
      <button
        @click="addToCart"
        :disabled="cart.loading || !selectedVariantId || !inStock"
        class="flex-1 rounded-xl border-2 border-brand-500 bg-white py-3 text-sm font-bold text-brand-600 transition-colors hover:bg-brand-50 active:bg-brand-100 disabled:opacity-50"
      >
        {{ addedToCart ? "✓ Added" : "Add to Cart" }}
      </button>
      <button
        @click="buyNow"
        :disabled="!selectedVariantId || !inStock"
        class="flex-1 rounded-xl bg-brand-500 py-3 text-sm font-bold text-white shadow-md transition-colors hover:bg-brand-600 active:bg-brand-700 disabled:opacity-50"
      >
        Buy Now
      </button>
    </div>
  </div>

  <!-- Photo Lightbox -->
  <PhotoLightbox
    v-if="lightboxOpen && images.length"
    :images="images"
    :start-index="lightboxIndex"
    :alt="product?.name ?? 'Product photo'"
    @close="lightboxOpen = false"
  />
</template>

<style scoped>
/* Add safe area support for iOS devices */
.pb-safe {
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}
</style>
