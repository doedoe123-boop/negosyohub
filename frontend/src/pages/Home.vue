<script setup>
import { ref, onMounted } from "vue";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { useSeoStore } from "@/stores/seo";
import { RouterLink } from "vue-router";
import {
  BuildingStorefrontIcon,
  HomeModernIcon,
  TruckIcon,
  KeyIcon,
} from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";
import { productsApi } from "@/api/products";
import { propertiesApi } from "@/api/properties";

// Hero + existing
import DicedHeroSection from "@/components/DicedHeroSection.vue";
import CategoryStrip from "@/components/CategoryStrip.vue";

// New homepage components
import VerifiedProperties from "@/components/homepage/VerifiedProperties.vue";
import TrendingCarousel from "@/components/homepage/TrendingCarousel.vue";
import TrustStrip from "@/components/homepage/TrustStrip.vue";
import AdBanner from "@/components/homepage/AdBanner.vue";
import PromotionBanner from "@/components/homepage/PromotionBanner.vue";

// Live backend stats composable
import { useHomepageStats } from "@/composables/useHomepageStats";
const { stats, loaded: statsLoaded, formatCount } = useHomepageStats();
const seo = useSeoStore();

useSeoMeta({ title: null, description: seo.defaultDescription });

const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";

const featuredStores = ref([]);
const featuredProducts = ref([]);
const latestProperties = ref([]);
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
    color: "bg-emerald-50 text-emerald-600 ring-emerald-200",
    badge: "bg-emerald-100 text-emerald-700",
  },
  {
    label: "Lipat Bahay",
    description: "Book verified moving companies near you",
    to: "/movers",
    icon: TruckIcon,
    color: "bg-violet-50 text-violet-600 ring-violet-200",
    badge: "bg-violet-100 text-violet-700",
  },
  {
    label: "Paupahan",
    description: "Find apartments & rooms for rent",
    to: "/properties?listing_type=for_rent",
    icon: KeyIcon,
    color: "bg-amber-50 text-amber-600 ring-amber-200",
    badge: "bg-amber-100 text-amber-700",
  },
];

// Spotlight uses first 3 stores; assign each a visual theme
const spotlightThemes = [
  { bg: "#0F2044", accent: "#059669", label: "Top Pick" },
  { bg: "#059669", accent: "#0F2044", label: "Featured" },
  { bg: "#1a1a2e", accent: "#f95d2f", label: "Trending" },
];

function peso(val) {
  return (
    "₱" +
    parseFloat(val ?? 0).toLocaleString("en-PH", { maximumFractionDigits: 0 })
  );
}

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
    const { data } = await productsApi.list({ per_page: 12 });
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
    <!-- ── 1. Hero ──────────────────────────────────────────────────── -->
    <div class="bg-navy-900">
      <DicedHeroSection
        top-text="The Filipino Marketplace"
        main-text="Shop. Rent. Move."
        sub-main-text="From online stores and real estate to moving services and rentals — everything your lifestyle needs, in one trusted platform."
        button-text="Explore Now"
        :slides="[
          {
            title: 'E-Commerce',
            image:
              'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=800&auto=format&fit=crop&q=80',
          },
          {
            title: 'Real Estate',
            image:
              'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=80',
          },
          {
            title: 'Lipat Bahay',
            image:
              'https://images.unsplash.com/photo-1600518464441-9154a4dea21b?w=800&auto=format&fit=crop&q=80',
          },
          {
            title: 'Paupahan',
            image:
              'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&auto=format&fit=crop&q=80',
          },
        ]"
        :on-main-button-click="() => $router.push('/stores')"
        :on-grid-image-click="
          (i) => {
            const routes = [
              '/stores',
              '/properties',
              '/movers',
              '/properties?listing_type=for_rent',
            ];
            $router.push(routes[i] ?? '/stores');
          }
        "
        background-color="transparent"
      />
    </div>

    <!-- ── 3. Category strip ───────────────────────────────────────── -->
    <CategoryStrip />

    <!-- ── 3a. Sponsored Ad Banner ─────────────────────────────────── -->
    <AdBanner placement="home_banner" />

    <!-- ── 4. Sector picker ────────────────────────────────────────── -->
    <section class="border-b border-slate-100 bg-white px-4 py-12 sm:px-6">
      <div class="mx-auto max-w-7xl">
        <div class="mb-7 text-center">
          <h2 class="text-2xl font-bold text-slate-900">
            What are you looking for?
          </h2>
          <p class="mt-1.5 text-sm text-slate-500">
            Browse our growing list of local sectors.
          </p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <component
            :is="sector.soon ? 'div' : RouterLink"
            v-for="sector in sectors"
            :key="sector.label"
            :to="sector.soon ? undefined : sector.to"
            class="group relative flex items-start gap-4 rounded-2xl border p-6 transition-all"
            :class="
              sector.soon
                ? 'cursor-not-allowed bg-slate-50 opacity-60'
                : 'cursor-pointer bg-white sector-card hover:-translate-y-0.5'
            "
          >
            <span
              class="flex size-12 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform group-hover:scale-105"
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
              <p class="mt-0.5 text-sm text-slate-500 line-clamp-2">
                {{ sector.description }}
              </p>
            </div>
            <svg
              v-if="!sector.soon"
              class="absolute right-4 top-1/2 size-4 -translate-y-1/2 text-slate-300 transition-all group-hover:text-emerald-400 group-hover:translate-x-0.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m9 18 6-6-6-6"
              />
            </svg>
          </component>
        </div>
      </div>
    </section>

    <!-- ── 5. Verified Properties ──────────────────────────────────── -->
    <VerifiedProperties
      :properties="latestProperties"
      :loading="propertiesLoading"
    />

    <!-- ── 6. Trending Products Carousel ───────────────────────────── -->
    <TrendingCarousel :products="featuredProducts" :loading="productsLoading" />

    <!-- ── 7. Featured Stores Spotlight ─────────────────────────────── -->
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div class="mb-5 flex items-end justify-between">
        <div>
          <p
            class="mb-1 text-xs font-semibold uppercase tracking-widest text-emerald-600"
          >
            Discover
          </p>
          <h2 class="text-2xl font-bold text-slate-900">Featured Stores</h2>
        </div>
        <RouterLink
          to="/stores"
          class="flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors"
        >
          View All
          <svg
            class="size-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"
            />
          </svg>
        </RouterLink>
      </div>

      <!-- Spotlight cards (top 3 stores) -->
      <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
          v-for="i in 3"
          :key="i"
          class="h-36 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>

      <div
        v-else-if="featuredStores.length > 0"
        class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6"
      >
        <RouterLink
          v-for="(store, i) in featuredStores.slice(0, 3)"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="group relative flex h-36 overflow-hidden rounded-2xl transition-all hover:-translate-y-0.5 spotlight-card"
          :style="{ background: spotlightThemes[i].bg }"
        >
          <!-- BG image overlay -->
          <img
            v-if="store.banner_url"
            :src="store.banner_url"
            :alt="store.name"
            class="absolute inset-0 h-full w-full object-cover opacity-20 transition-opacity group-hover:opacity-30"
          />
          <!-- Content -->
          <div
            class="relative z-10 flex h-full w-full flex-col justify-between p-4"
          >
            <div class="flex items-start justify-between">
              <!-- Store logo bubble -->
              <div
                class="flex size-10 items-center justify-center overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20 text-xl"
              >
                <img
                  v-if="store.logo_url"
                  :src="store.logo_url"
                  :alt="store.name"
                  class="h-full w-full object-cover"
                />
                <span v-else>🏪</span>
              </div>
              <!-- Badge -->
              <span
                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold text-white"
                :style="{ background: spotlightThemes[i].accent }"
                >{{ spotlightThemes[i].label }}</span
              >
            </div>
            <div>
              <p class="text-sm font-bold text-white line-clamp-1">
                {{ store.name }}
              </p>
              <p class="mt-0.5 text-xs text-white/60">
                {{ store.sector_label ?? "Local Store" }}
              </p>
              <p
                class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-white/90 transition-all group-hover:gap-2"
              >
                Visit Store →
              </p>
            </div>
          </div>
        </RouterLink>
      </div>

      <!-- Regular store grid (rest of stores) -->
      <div
        v-if="!loading && featuredStores.length > 3"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
      >
        <RouterLink
          v-for="store in featuredStores.slice(3)"
          :key="store.id"
          :to="`/stores/${store.slug}`"
          class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white hover:-translate-y-0.5 transition-all store-card"
        >
          <div class="aspect-[3/2] w-full overflow-hidden bg-slate-100">
            <img
              v-if="store.banner_url"
              :src="store.banner_url"
              :alt="store.name"
              class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div
              v-else
              class="flex h-full items-center justify-center"
              :class="
                store.sector_template === 'real_estate'
                  ? 'bg-gradient-to-br from-slate-100 to-slate-200'
                  : 'bg-gradient-to-br from-brand-50 to-brand-100'
              "
            >
              <span class="text-3xl">{{
                store.sector_template === "real_estate" ? "🏠" : "🛍️"
              }}</span>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3">
            <img
              v-if="store.logo_url"
              :src="store.logo_url"
              :alt="store.name"
              class="size-10 shrink-0 rounded-xl bg-slate-100 object-cover ring-2 ring-white shadow-sm"
            />
            <div
              v-else
              class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-lg"
            >
              🏪
            </div>
            <div class="min-w-0">
              <p
                class="truncate text-sm font-semibold text-slate-800 group-hover:text-emerald-700 transition-colors"
              >
                {{ store.name }}
              </p>
              <p class="text-xs text-slate-400">
                {{ store.sector_label ?? store.sector }}
              </p>
            </div>
          </div>
        </RouterLink>
      </div>

      <!-- All stores skeleton / empty -->
      <div
        v-else-if="loading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
      >
        <div
          v-for="i in 4"
          :key="i"
          class="h-44 animate-pulse rounded-2xl bg-slate-100"
        />
      </div>
      <div
        v-else-if="featuredStores.length === 0"
        class="rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center"
      >
        <p class="text-2xl mb-2">🏪</p>
        <p class="text-sm font-medium text-slate-500">
          No featured stores yet — check back soon!
        </p>
      </div>
    </section>

    <!-- ── 7a. Promotions ──────────────────────────────────────────── -->
    <PromotionBanner />

    <!-- ── 8. Trust Signals Strip ──────────────────────────────────── -->
    <TrustStrip :stats="stats" />

    <!-- ── 9. Seller CTA banner ────────────────────────────────────── -->
    <section class="bg-navy-900 py-14 text-white">
      <div class="mx-auto max-w-7xl px-4 text-center sm:px-6">
        <p
          class="mb-3 text-xs font-semibold uppercase tracking-widest text-emerald-400"
        >
          For Business Owners
        </p>
        <h2 class="text-3xl font-bold">Grow your business with NegosyoHub</h2>
        <p class="mx-auto mt-3 max-w-xl text-base text-white/60">
          List your store, manage orders and products, and reach thousands of
          local customers — free to get started.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
          <a
            :href="`${backendUrl}/register/sector`"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 hover:shadow-emerald-500/30 hover:shadow-lg active:bg-emerald-700 transition-all"
          >
            Register your store
            <svg
              class="size-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"
              />
            </svg>
          </a>
          <RouterLink
            to="/stores"
            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors"
          >
            Browse stores
          </RouterLink>
        </div>

        <!-- Trust note -->
        <p
          class="mt-6 flex items-center justify-center gap-1.5 text-xs text-white/40"
        >
          <svg
            class="size-3.5 text-emerald-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
            />
          </svg>
          🔒 Your data is protected · Trusted by
          {{ formatCount(stats.stores) }} sellers
        </p>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Quiet Luxury card shadows from DESIGN.md */
.sector-card {
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.08),
    0 1px 2px rgba(0, 0, 0, 0.04);
}
.sector-card:hover {
  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.1),
    0 2px 4px rgba(0, 0, 0, 0.06);
}
.spotlight-card {
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.08),
    0 1px 2px rgba(0, 0, 0, 0.04);
}
.spotlight-card:hover {
  box-shadow: 0 8px 24px rgba(15, 32, 68, 0.2);
}
.store-card {
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.08),
    0 1px 2px rgba(0, 0, 0, 0.04);
}
.store-card:hover {
  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.1),
    0 2px 4px rgba(0, 0, 0, 0.06);
}
</style>
