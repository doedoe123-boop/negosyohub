<script setup>
import PropertyCard from "./PropertyCard.vue";

defineProps({
  properties: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
</script>

<template>
  <section class="theme-page-section-muted py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <!-- Header -->
      <div class="mb-7 flex items-end justify-between">
        <div>
          <p
            class="mb-1 text-xs font-semibold uppercase tracking-widest text-emerald-600"
          >
            Real Estate
          </p>
          <h2 class="theme-title text-2xl font-bold">Verified Properties</h2>
          <p class="theme-copy mt-1 text-sm">
            Government-verified listings from trusted agencies across the
            Philippines.
          </p>
        </div>
        <RouterLink
          to="/properties"
          class="flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors"
        >
          See all properties
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

      <!-- Skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
      >
        <div
          v-for="i in 4"
          :key="i"
          class="theme-card animate-pulse rounded-2xl"
        >
          <div
            class="aspect-[16/9] rounded-t-2xl"
            style="background-color: var(--color-surface-muted)"
          />
          <div class="p-4 space-y-2">
            <div class="h-4 w-3/4 rounded" style="background-color: var(--color-surface-muted)" />
            <div class="h-3 w-1/2 rounded" style="background-color: var(--color-surface-muted)" />
            <div class="h-5 w-1/3 rounded" style="background-color: color-mix(in srgb, var(--color-border) 80%, transparent)" />
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="properties.length === 0"
        class="theme-card rounded-2xl border-dashed py-14 text-center"
      >
        <p class="text-2xl mb-2">🏡</p>
        <p class="theme-copy text-sm font-medium">
          No verified listings yet — check back soon!
        </p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <PropertyCard
          v-for="property in properties"
          :key="property.id"
          :property="property"
        />
      </div>
    </div>
  </section>
</template>
