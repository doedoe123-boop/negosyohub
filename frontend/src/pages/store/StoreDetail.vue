<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { ChevronRightIcon, MapPinIcon } from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";
import { useCartStore } from "@/stores/cart";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
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
                {{ store.sector_label ?? store.sector }}
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
        <h2 class="mb-4 text-xl font-bold text-slate-900">Products</h2>

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
            class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md"
          >
            <!-- Low-stock badge -->
            <span
              v-if="
                product.stock != null &&
                product.stock <= 10 &&
                product.stock > 0
              "
              class="absolute left-2 top-2 z-10 rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shadow"
            >
              Only {{ product.stock }} left
            </span>
            <!-- Out-of-stock badge -->
            <span
              v-else-if="product.stock != null && product.stock <= 0"
              class="absolute left-2 top-2 z-10 rounded-full bg-slate-500 px-2 py-0.5 text-[10px] font-bold text-white shadow"
            >
              Sold out
            </span>

            <!-- Image — clicking goes to product detail -->
            <RouterLink :to="`/products/${product.id}`" class="block">
              <div class="aspect-square w-full overflow-hidden bg-slate-100">
                <img
                  v-if="product.thumbnail"
                  :src="product.thumbnail"
                  :alt="product.name"
                  class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div
                  v-else
                  class="flex h-full items-center justify-center text-4xl"
                >
                  🛍️
                </div>
              </div>
            </RouterLink>

            <!-- Info -->
            <div class="flex flex-1 flex-col p-3">
              <RouterLink :to="`/products/${product.id}`">
                <p
                  class="line-clamp-2 text-sm font-semibold leading-snug text-slate-800 transition-colors group-hover:text-brand-600"
                >
                  {{ product.name }}
                </p>
              </RouterLink>
              <p class="mt-1.5 text-base font-extrabold text-slate-900">
                {{
                  product.price != null
                    ? "₱" +
                      parseFloat(product.price).toLocaleString("en-PH", {
                        maximumFractionDigits: 0,
                      })
                    : "—"
                }}
              </p>
              <!-- Add to Cart — always visible, no overlap conflict -->
              <button
                type="button"
                class="mt-auto mt-3 w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:from-brand-600 hover:to-brand-700 hover:shadow-brand-500/25 hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="
                  cart.loading || (product.stock != null && product.stock <= 0)
                "
                @click.prevent="addToCart(product)"
              >
                {{
                  product.stock != null && product.stock <= 0
                    ? "Out of Stock"
                    : "Add to Cart"
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
