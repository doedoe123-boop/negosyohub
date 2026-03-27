<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { promotionsApi } from "@/api/promotions";
import { TagIcon, FireIcon, SparklesIcon } from "@heroicons/vue/24/outline";

const promotions = ref([]);
const loaded = ref(false);

const typeStyles = {
  holiday_sale: {
    icon: SparklesIcon,
    color: "text-red-500 bg-red-50 ring-red-200 dark:bg-red-900/30 dark:ring-red-900/50",
  },
  seasonal_promotion: {
    icon: TagIcon,
    color: "text-emerald-600 bg-emerald-50 ring-emerald-200 dark:bg-emerald-900/30 dark:ring-emerald-900/50",
  },
  marketplace_discount: {
    icon: TagIcon,
    color: "text-brand-600 bg-brand-50 ring-brand-200 dark:bg-brand-900/30 dark:ring-brand-900/50",
  },
  flash_sale: {
    icon: FireIcon,
    color: "text-amber-500 bg-amber-50 ring-amber-200 dark:bg-amber-900/30 dark:ring-amber-900/50",
  },
  anniversary: {
    icon: SparklesIcon,
    color: "text-indigo-500 bg-indigo-50 ring-indigo-200 dark:bg-indigo-900/30 dark:ring-indigo-900/50",
  },
  clearance: {
    icon: TagIcon,
    color: "text-slate-600 bg-slate-50 ring-slate-200 dark:bg-slate-700 dark:ring-slate-600 dark:text-slate-300",
  },
};

function discountLabel(promo) {
  if (promo.discount_percentage) {
    return `${promo.discount_percentage}% OFF`;
  }
  if (promo.discount_amount_cents) {
    return `₱${(promo.discount_amount_cents / 100).toLocaleString("en-PH")} OFF`;
  }
  return "Sale";
}

function daysLeft(endsAt) {
  if (!endsAt) return null;
  const diff = Math.ceil((new Date(endsAt) - new Date()) / 86_400_000);
  if (diff <= 0) return null;
  if (diff === 1) return "Ends tomorrow";
  if (diff <= 7) return `${diff} days left`;
  return null;
}

onMounted(async () => {
  try {
    const { data } = await promotionsApi.list({ limit: 4 });
    promotions.value = data;
  } catch {
    // Non-critical
  } finally {
    loaded.value = true;
  }
});
</script>

<template>
  <section
    v-if="loaded && promotions.length > 0"
    class="border-b border-slate-100 bg-white px-4 py-12 sm:px-6 dark:bg-slate-900 dark:border-slate-800"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <div class="mb-7 flex items-end justify-between">
        <div>
          <p
            class="mb-1 text-xs font-semibold uppercase tracking-widest text-red-500"
          >
            Limited Time
          </p>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Active Promotions</h2>
        </div>
        <RouterLink
          to="/stores"
          class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors"
        >
          Shop Now →
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="promo in promotions"
          :key="promo.id"
          class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 transition-all hover:-translate-y-0.5 promo-card dark:bg-slate-800 dark:border-slate-700"
        >
          <!-- Type icon -->
          <span
            class="mb-3 inline-flex size-10 items-center justify-center rounded-xl ring-1"
            :class="(typeStyles[promo.type] ?? typeStyles.clearance).color"
          >
            <component
              :is="(typeStyles[promo.type] ?? typeStyles.clearance).icon"
              class="size-5"
            />
          </span>

          <!-- Discount badge -->
          <p class="mb-1 text-xl font-extrabold text-slate-900 dark:text-white">
            {{ discountLabel(promo) }}
          </p>

          <div class="text-sm font-semibold text-slate-700 dark:text-slate-200 line-clamp-1">
            {{ promo.name }}
          </div>
          <div
            v-if="promo.description"
            v-html="promo.description"
            class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2"
          ></div>

          <!-- Urgency label -->
          <p
            v-if="daysLeft(promo.ends_at)"
            class="mt-3 text-[11px] font-bold uppercase tracking-wide text-red-500"
          >
            🔥 {{ daysLeft(promo.ends_at) }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.promo-card {
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.08),
    0 1px 2px rgba(0, 0, 0, 0.04);
}
.promo-card:hover {
  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.1),
    0 2px 4px rgba(0, 0, 0, 0.06);
}
</style>
