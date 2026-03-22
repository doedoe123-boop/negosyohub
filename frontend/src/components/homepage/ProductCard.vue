<script setup>
const props = defineProps({
  product: { type: Object, required: true },
});

function peso(val) {
  return (
    "₱" +
    parseFloat(val ?? 0).toLocaleString("en-PH", { maximumFractionDigits: 0 })
  );
}

function discount(product) {
  const orig = parseFloat(
    product.compare_at_price ?? product.original_price ?? 0,
  );
  const curr = parseFloat(product.price ?? 0);
  if (!orig || !curr || orig <= curr) return null;
  return {
    pct: Math.round((1 - curr / orig) * 100),
    save: Math.round(orig - curr),
    original: orig,
  };
}
</script>

<template>
  <RouterLink
    :to="`/products/${product.id}`"
    class="theme-card theme-card-hover group relative flex flex-col overflow-hidden rounded-2xl transition-all duration-150 ease-out"
  >
    <!-- Image -->
    <div
      class="relative aspect-square overflow-hidden"
      style="background-color: var(--color-surface)"
    >
      <img
        v-if="product.thumbnail"
        :src="product.thumbnail"
        :alt="product.name"
        class="h-full w-full object-contain p-2 transition-transform duration-300 group-hover:scale-105"
      />
      <div
        v-else
        class="flex h-full items-center justify-center bg-gradient-to-br from-brand-50 to-slate-100 text-4xl"
      >
        🛍️
      </div>

      <!-- Discount badge -->
      <span
        v-if="discount(product)"
        class="absolute right-2 top-2 rounded-lg bg-brand-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm"
      >
        {{ discount(product).pct }}% OFF
      </span>
    </div>

    <!-- Info -->
    <div class="flex flex-1 flex-col p-3">
      <p
        class="line-clamp-2 text-sm font-medium leading-snug transition-colors group-hover:text-emerald-700"
        style="color: var(--color-text)"
      >
        {{ product.name }}
      </p>

      <!-- Rating -->
      <div v-if="product.average_rating" class="mt-1.5 flex items-center gap-1">
        <span class="text-xs text-amber-500">⭐</span>
        <span class="theme-copy text-xs font-semibold">{{
          product.average_rating
        }}</span>
        <span v-if="product.review_count" class="theme-copy text-xs">
          · {{ product.review_count }} reviews
        </span>
      </div>

      <div class="mt-auto pt-2">
        <!-- Current price -->
        <p class="text-lg font-bold text-brand-500">
          {{ peso(product.price) }}
        </p>
        <!-- Original + savings -->
        <template v-if="discount(product)">
          <p class="theme-copy text-xs line-through">
            {{ peso(discount(product).original) }}
          </p>
          <p class="text-xs font-semibold text-emerald-600">
            Save {{ peso(discount(product).save) }}
          </p>
        </template>
      </div>
    </div>
  </RouterLink>
</template>
