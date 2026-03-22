<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import ProductCard from "./ProductCard.vue";

const props = defineProps({
  products: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const scrollContainer = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(true);

function checkScroll() {
  const el = scrollContainer.value;
  if (!el) return;
  canScrollLeft.value = el.scrollLeft > 4;
  canScrollRight.value = el.scrollLeft < el.scrollWidth - el.clientWidth - 4;
}

function scroll(dir) {
  const el = scrollContainer.value;
  if (!el) return;
  const cardWidth = el.querySelector(".carousel-item")?.offsetWidth ?? 260;
  el.scrollBy({ left: dir * (cardWidth + 16), behavior: "smooth" });
}

onMounted(() => {
  scrollContainer.value?.addEventListener("scroll", checkScroll, {
    passive: true,
  });
  checkScroll();
});

onBeforeUnmount(() => {
  scrollContainer.value?.removeEventListener("scroll", checkScroll);
});
</script>

<template>
  <section class="theme-page-section py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <!-- Header -->
      <div class="mb-6 flex items-end justify-between">
        <div>
          <p
            class="mb-1 text-xs font-semibold uppercase tracking-widest text-brand-500"
          >
            E-Commerce
          </p>
          <h2 class="theme-title text-2xl font-bold">Trending Products</h2>
          <p class="theme-copy mt-1 text-sm">
            Popular picks from local Filipino stores.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <!-- Scroll arrows (desktop) -->
          <button
            @click="scroll(-1)"
            :disabled="!canScrollLeft"
            class="theme-card hidden size-8 items-center justify-center rounded-full transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-30 sm:flex"
          >
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
                d="M15.75 19.5L8.25 12l7.5-7.5"
              />
            </svg>
          </button>
          <button
            @click="scroll(1)"
            :disabled="!canScrollRight"
            class="theme-card hidden size-8 items-center justify-center rounded-full transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-30 sm:flex"
          >
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
                d="M8.25 4.5l7.5 7.5-7.5 7.5"
              />
            </svg>
          </button>

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
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="flex gap-4 overflow-hidden">
        <div
          v-for="i in 5"
          :key="i"
          class="theme-card w-[220px] shrink-0 animate-pulse rounded-2xl"
        >
          <div class="aspect-square rounded-t-2xl" style="background-color: var(--color-surface-muted)" />
          <div class="p-3 space-y-2">
            <div class="h-4 w-3/4 rounded" style="background-color: var(--color-surface-muted)" />
            <div class="h-4 w-1/2 rounded" style="background-color: color-mix(in srgb, var(--color-border) 80%, transparent)" />
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="products.length === 0"
        class="theme-card rounded-2xl border-dashed py-14 text-center"
      >
        <p class="text-2xl mb-2">🛍️</p>
        <p class="theme-copy text-sm font-medium">
          No trending products yet — check back soon!
        </p>
      </div>

      <!-- Carousel -->
      <div v-else class="relative">
        <!-- Gradient fade edges -->
        <div
          v-if="canScrollLeft"
          class="pointer-events-none absolute left-0 top-0 z-10 h-full w-12 bg-gradient-to-r to-transparent"
          style="--tw-gradient-from: var(--color-bg) var(--tw-gradient-from-position)"
        />
        <div
          v-if="canScrollRight"
          class="pointer-events-none absolute right-0 top-0 z-10 h-full w-12 bg-gradient-to-l to-transparent"
          style="--tw-gradient-from: var(--color-bg) var(--tw-gradient-from-position)"
        />

        <div
          ref="scrollContainer"
          class="scrollbar-none flex gap-4 overflow-x-auto scroll-smooth pb-2"
        >
          <div
            v-for="product in products"
            :key="product.id"
            class="carousel-item w-[200px] shrink-0 sm:w-[220px]"
          >
            <ProductCard :product="product" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
