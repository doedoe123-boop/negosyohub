<script setup>
import { computed } from "vue";

const props = defineProps({
  property: { type: Object, required: true },
});

const listingLabel = {
  for_sale: "For Sale",
  for_rent: "For Rent",
  for_lease: "For Lease",
  pre_selling: "Pre-Selling",
};

const listingBadgeClass = {
  for_sale: "bg-emerald-100 text-emerald-700",
  for_rent: "bg-sky-100 text-sky-700",
  for_lease: "bg-amber-100 text-amber-700",
  pre_selling: "bg-purple-100 text-purple-700",
};

function peso(val) {
  return (
    "₱" +
    parseFloat(val ?? 0).toLocaleString("en-PH", { maximumFractionDigits: 0 })
  );
}

const monthlyEstimate = computed(() => {
  const price = parseFloat(props.property.price ?? 0);
  if (!price || price < 100000) return null;
  // Simple Pag-IBIG estimate: ~0.007 of total price per month
  const monthly = Math.round(price * 0.007);
  return peso(monthly);
});
</script>

<template>
  <RouterLink
    :to="`/properties/${property.slug}`"
    class="theme-card theme-card-hover group flex flex-col overflow-hidden rounded-2xl transition-all duration-150 ease-out"
  >
    <!-- Image -->
    <div
      class="relative aspect-[16/9] overflow-hidden"
      style="background-color: var(--color-surface-muted)"
    >
      <img
        v-if="property.images && property.images[0]"
        :src="property.images[0]"
        :alt="property.title"
        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
      />
      <div
        v-else
        class="flex h-full items-center justify-center bg-gradient-to-br from-navy-50 to-slate-100 text-4xl"
      >
        🏡
      </div>

      <!-- Listing type badge -->
      <span
        class="absolute left-2.5 top-2.5 rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm"
        :class="
          listingBadgeClass[property.listing_type] ??
          'theme-card-muted'
        "
      >
        {{ listingLabel[property.listing_type] ?? property.listing_type }}
      </span>

      <!-- Verified overlay badge -->
      <span
        class="absolute right-2.5 top-2.5 inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50/90 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm backdrop-blur-sm"
      >
        <svg
          class="size-3 text-emerald-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2.5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
          />
        </svg>
        Verified
      </span>
    </div>

    <!-- Info -->
    <div class="flex flex-1 flex-col p-4">
      <p
        class="line-clamp-2 font-semibold leading-snug transition-colors group-hover:text-emerald-700"
        style="color: var(--color-text)"
      >
        {{ property.title }}
      </p>

      <p class="theme-copy mt-1.5 flex items-center gap-1 text-xs">
        <svg
          class="size-3 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
          />
        </svg>
        {{ property.city }}
      </p>

      <!-- Social Proof -->
      <p
        v-if="property.average_rating || property.views_count"
        class="theme-copy mt-1.5 flex items-center gap-1.5 text-xs font-medium"
      >
        <span v-if="property.average_rating"
          >⭐ {{ property.average_rating }}</span
        >
        <span v-if="property.average_rating && property.views_count"> · </span>
        <span v-if="property.views_count" class="flex items-center gap-1">
          <svg
            class="size-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path
              d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
            />
            <circle cx="12" cy="12" r="3" />
          </svg>
          {{ property.views_count }}
        </span>
      </p>

      <div class="mt-auto pt-3">
        <!-- Price -->
        <p class="text-lg font-bold text-brand-500">
          {{ peso(property.price) }}
        </p>

        <!-- Pag-IBIG estimate -->
        <p v-if="monthlyEstimate" class="theme-copy mt-0.5 text-xs">
          Est. {{ monthlyEstimate }}/mo via Pag-IBIG
        </p>
      </div>

      <!-- Verified Agency inline -->
      <div
        v-if="property.agency"
        class="theme-divider-soft mt-3 flex items-center gap-2 border-t pt-3"
      >
        <div
          class="flex size-6 items-center justify-center rounded-full bg-emerald-50 text-xs"
        >
          <svg
            class="size-3.5 text-emerald-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
            />
          </svg>
        </div>
        <span class="theme-copy text-xs font-medium">
          {{ property.agency?.name ?? "Verified Agency" }}
        </span>
      </div>
    </div>
  </RouterLink>
</template>
