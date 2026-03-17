<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import {
  ChevronRightIcon,
  MapPinIcon,
  PhoneIcon,
  GlobeAltIcon,
} from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { useCartStore } from "@/stores/cart";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const cart = useCartStore();

const store = ref(null);
const products = ref([]);
const properties = ref([]);
const loading = ref(true);
const error = ref(null);

const isRealEstate = computed(() => store.value?.sector === "real_estate");

const listingLabels = {
  for_sale: "For Sale",
  for_rent: "For Rent",
  for_lease: "For Lease",
  pre_selling: "Pre-Selling",
};

function formatPrice(price, currency = "PHP") {
  if (!price && price !== 0) return "";
  return parseFloat(price).toLocaleString("en-PH", {
    style: "currency",
    currency: currency || "PHP",
    maximumFractionDigits: 0,
  });
}

onMounted(async () => {
  try {
    const storeRes = await storesApi.show(route.params.slug);
    store.value = storeRes.data;

    useSeoMeta({
      title: store.value.seo_title || store.value.name,
      description: store.value.seo_description || store.value.description,
      ogImage: store.value.og_image || store.value.banner_url || null,
      ogType: "business.business",
    });

    if (store.value.sector === "real_estate") {
      const propRes = await storesApi.properties(route.params.slug);
      properties.value = propRes.data?.data ?? propRes.data;
    } else {
      const prodRes = await storesApi.products(route.params.slug);
      products.value = prodRes.data?.data ?? prodRes.data;
    }
  } catch (e) {
    error.value =
      e.response?.status === 404 ? "Store not found." : "Failed to load store.";
  } finally {
    loading.value = false;
  }
});

async function addToCart(product) {
  if (!auth.isLoggedIn) {
    router.push({ name: "auth.login", query: { redirect: route.fullPath } });
    return;
  }
  await cart.addItem("product-variant", product.default_variant_id, 1, {
    store_id: store.value.id,
  });
}
</script>

<template>
  <div>
    <!-- Skeleton -->
    <div v-if="loading" class="animate-pulse">
      <!-- Banner -->
      <div class="h-52 w-full bg-slate-200" />
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <!-- Store info bar -->
        <div class="relative -mt-10 mb-6 flex items-end gap-4">
          <div
            class="size-20 shrink-0 rounded-2xl bg-slate-300 ring-4 ring-white shadow-md"
          />
          <div class="mb-2 space-y-2">
            <div class="h-6 w-40 rounded-lg bg-slate-200" />
            <div class="h-4 w-24 rounded-full bg-slate-100" />
          </div>
        </div>
        <!-- Description -->
        <div class="mb-8 space-y-2">
          <div class="h-3.5 w-full max-w-xl rounded bg-slate-100" />
          <div class="h-3.5 w-2/3 max-w-md rounded bg-slate-100" />
        </div>
        <!-- Product grid -->
        <div class="h-6 w-24 rounded-lg bg-slate-200 mb-4" />
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          <div
            v-for="i in 8"
            :key="i"
            class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100"
          >
            <div class="aspect-square w-full rounded-t-2xl bg-slate-100" />
            <div class="p-3 space-y-2">
              <div class="h-4 w-3/4 rounded bg-slate-100" />
              <div class="h-5 w-1/2 rounded bg-slate-200" />
              <div class="h-9 w-full rounded-xl bg-slate-100" />
            </div>
          </div>
        </div>
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
          v-if="store.banner_url"
          :src="store.banner_url"
          :alt="store.name"
          class="h-full w-full object-cover"
        />
        <div
          v-else
          class="h-full w-full bg-gradient-to-br"
          :class="store.sector_theme ?? 'from-slate-700 to-slate-900'"
        />
        <!-- Subtle overlay for text contrast -->
        <div class="absolute inset-0 bg-black/20" />
      </div>

      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <!-- Store info bar (overlaps banner) -->
        <div class="relative -mt-10 mb-6 flex items-end gap-4">
          <div
            v-if="store.logo"
            class="size-[100px] shrink-0 overflow-hidden rounded-full border-4 border-white bg-white shadow-lg"
          >
            <img
              :src="store.logo"
              :alt="store.name"
              class="h-full w-full object-cover"
            />
          </div>
          <div
            v-else
            class="flex size-[100px] shrink-0 items-center justify-center rounded-full border-4 border-white shadow-lg bg-[#0F2044] text-5xl font-bold text-white relative flex-col"
          >
            {{ store.name?.charAt(0).toUpperCase() }}
          </div>

          <div class="mb-2 min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <h1
                class="text-3xl font-bold leading-tight text-[#0F2044] break-words"
              >
                {{ store.name }}
              </h1>
              <span
                v-if="store.sector"
                class="shrink-0 whitespace-nowrap rounded-full bg-brand-50 border border-brand-100 px-3 py-1 text-xs font-bold text-brand-600"
              >
                {{ store.sector_label ?? store.sector }}
              </span>
            </div>
            <p v-if="store.tagline" class="text-slate-500 font-medium mt-1">
              {{ store.tagline }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-8 mb-12">
          <!-- Left Sidebar (Contact Info) -->
          <div class="space-y-4">
            <div
              class="flex items-start gap-4 p-4 rounded-2xl bg-white shadow-sm border border-slate-100"
            >
              <PhoneIcon class="size-5 text-brand-600 shrink-0" />
              <div class="flex flex-col min-w-0">
                <span class="text-sm font-bold text-[#0F2044] truncate">{{
                  store.phone || "N/A"
                }}</span>
                <span class="text-xs text-slate-500">Official Contact</span>
              </div>
            </div>

            <div
              class="flex items-start gap-4 p-4 rounded-2xl bg-white shadow-sm border border-slate-100 hover:bg-slate-50 transition-colors"
            >
              <GlobeAltIcon class="size-5 text-brand-600 shrink-0" />
              <div class="flex flex-col min-w-0">
                <a
                  v-if="store.website"
                  :href="store.website"
                  target="_blank"
                  class="text-sm font-bold text-[#0F2044] hover:text-brand-600 transition-colors truncate"
                  >{{ store.website.replace(/^https?:\/\//, "") }}</a
                >
                <span v-else class="text-sm font-bold text-[#0F2044]">N/A</span>
                <span class="text-xs text-slate-500">Website</span>
              </div>
            </div>

            <div
              class="flex items-start gap-4 p-4 rounded-2xl bg-white shadow-sm border border-slate-100"
            >
              <MapPinIcon class="size-5 text-brand-600 shrink-0 mt-0.5" />
              <div class="flex flex-col min-w-0">
                <span class="text-sm font-bold text-[#0F2044] leading-snug">
                  {{
                    store.address?.line_one
                      ? store.address.line_one + ", "
                      : ""
                  }}{{ store.address?.city || "Location unavailable" }}
                </span>
                <span class="text-xs text-slate-500 mt-0.5"
                  >Store Location</span
                >
              </div>
            </div>

            <!-- Description Box -->
            <div
              v-if="store.description"
              class="p-5 rounded-2xl bg-slate-50 border border-slate-100 mt-6"
            >
              <h3 class="text-sm font-bold text-[#0F2044] mb-2">
                {{ isRealEstate ? "About Agency" : "About Store" }}
              </h3>
              <p class="text-xs leading-relaxed text-slate-600">
                {{ store.description }}
              </p>
            </div>
          </div>

          <!-- Right Column -->
          <div>
            <!-- ═══ Real Estate: Properties ═══ -->
            <template v-if="isRealEstate">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-[#0F2044]">
                  Listed Properties
                </h2>
              </div>

              <div
                v-if="properties.length === 0"
                class="rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-sm"
              >
                <div
                  class="mx-auto flex size-16 items-center justify-center rounded-full bg-slate-100 mb-4"
                >
                  <span class="text-2xl">🏠</span>
                </div>
                <h3 class="text-lg font-bold text-[#0F2044]">
                  No Properties Listed
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  This agency hasn't listed any properties yet.
                </p>
              </div>

              <div
                v-else
                class="grid grid-cols-1 gap-4 pb-16 sm:grid-cols-2 lg:grid-cols-3"
              >
                <RouterLink
                  v-for="property in properties"
                  :key="property.id"
                  :to="`/properties/${property.slug}`"
                  class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                >
                  <!-- Image -->
                  <div
                    class="relative aspect-[4/3] bg-slate-50 overflow-hidden"
                  >
                    <span
                      v-if="property.listing_type"
                      class="absolute left-2 top-2 z-10 rounded-full bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold text-white shadow"
                    >
                      {{
                        listingLabels[property.listing_type] ??
                        property.listing_type
                      }}
                    </span>
                    <img
                      v-if="property.images?.[0]"
                      :src="property.images[0]"
                      :alt="property.title"
                      class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div
                      v-else
                      class="flex h-full items-center justify-center text-3xl opacity-50"
                    >
                      🏠
                    </div>
                  </div>

                  <!-- Info -->
                  <div
                    class="flex flex-1 flex-col p-4 border-t border-slate-50"
                  >
                    <p
                      class="line-clamp-2 text-sm font-medium leading-snug text-[#0F2044] transition-colors group-hover:text-brand-600 min-h-[40px]"
                    >
                      {{ property.title }}
                    </p>
                    <p
                      v-if="property.city"
                      class="mt-1 flex items-center gap-1 text-xs text-slate-500"
                    >
                      <MapPinIcon class="size-3.5 shrink-0" />
                      {{ property.city }}
                    </p>
                    <p class="mt-2 text-lg font-bold text-[#F95D2F]">
                      {{
                        property.price
                          ? formatPrice(property.price, property.price_currency)
                          : "Contact for price"
                      }}
                    </p>
                    <div
                      v-if="
                        property.bedrooms ||
                        property.bathrooms ||
                        property.floor_area
                      "
                      class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500 border-t border-slate-50 pt-3"
                    >
                      <span v-if="property.bedrooms"
                        >🛏️ {{ property.bedrooms }} bed</span
                      >
                      <span v-if="property.bathrooms"
                        >🚿 {{ property.bathrooms }} bath</span
                      >
                      <span v-if="property.floor_area"
                        >📐 {{ property.floor_area }} sqm</span
                      >
                    </div>
                  </div>
                </RouterLink>
              </div>
            </template>

            <!-- ═══ E-Commerce / Other: Products ═══ -->
            <template v-else>
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-[#0F2044]">
                  Featured Products
                </h2>
              </div>

              <div
                v-if="products.length === 0"
                class="rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-sm"
              >
                <div
                  class="mx-auto flex size-16 items-center justify-center rounded-full bg-slate-100 mb-4"
                >
                  <span class="text-2xl">🛍️</span>
                </div>
                <h3 class="text-lg font-bold text-[#0F2044]">
                  No Products Found
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  This store hasn't listed any products yet.
                </p>
              </div>

              <div
                v-else
                class="grid grid-cols-2 gap-4 pb-16 sm:grid-cols-3 lg:grid-cols-4"
              >
                <div
                  v-for="product in products"
                  :key="product.id"
                  class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                >
                  <!-- Image -->
                  <RouterLink
                    :to="`/products/${product.id}`"
                    class="block relative aspect-square bg-slate-50 overflow-hidden"
                  >
                    <span
                      v-if="
                        product.stock != null &&
                        product.stock <= 10 &&
                        product.stock > 0
                      "
                      class="absolute left-2 top-2 z-10 rounded-full bg-orange-500 px-2.5 py-0.5 text-[10px] font-bold text-white shadow"
                    >
                      Low Stock
                    </span>
                    <span
                      v-else-if="product.stock != null && product.stock <= 0"
                      class="absolute left-2 top-2 z-10 rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-white shadow"
                    >
                      Sold Out
                    </span>

                    <img
                      v-if="product.thumbnail"
                      :src="product.thumbnail"
                      :alt="product.name"
                      class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div
                      v-else
                      class="flex h-full items-center justify-center text-3xl opacity-50"
                    >
                      📦
                    </div>
                  </RouterLink>

                  <!-- Info -->
                  <div
                    class="flex flex-1 flex-col p-4 border-t border-slate-50"
                  >
                    <RouterLink :to="`/products/${product.id}`">
                      <p
                        class="line-clamp-2 text-sm font-medium leading-snug text-[#0F2044] transition-colors group-hover:text-brand-600 min-h-[40px]"
                      >
                        {{ product.name }}
                      </p>
                    </RouterLink>
                    <p class="mt-2 text-lg font-bold text-[#F95D2F]">
                      {{
                        product.price != null
                          ? "₱" +
                            parseFloat(product.price).toLocaleString("en-PH", {
                              maximumFractionDigits: 0,
                            })
                          : "—"
                      }}
                    </p>
                    <!-- Add to Cart -->
                    <button
                      type="button"
                      class="mt-4 w-full rounded-xl bg-brand-600 py-2.5 text-xs font-bold text-white transition-all hover:bg-brand-500 active:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
                      :disabled="
                        cart.loading ||
                        (product.stock != null && product.stock <= 0)
                      "
                      @click.prevent="addToCart(product)"
                    >
                      <svg
                        class="size-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                      </svg>
                      {{
                        product.stock != null && product.stock <= 0
                          ? "Out of Stock"
                          : "Add to Cart"
                      }}
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
