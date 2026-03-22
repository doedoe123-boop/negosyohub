<script setup>
import { computed } from "vue";

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      stores: null,
      products: null,
      properties: null,
      average_rating: null,
      total_reviews: null,
    }),
  },
});

function formatCount(value) {
  if (value === null || value === undefined) return "–";
  if (value >= 1000) {
    const k = value / 1000;
    return k % 1 === 0 ? `${k}K+` : `${k.toFixed(1)}K+`;
  }
  return `${value}`;
}

const signals = computed(() => [
  {
    icon: "shield-check",
    label: `${formatCount(props.stats.stores)} Verified Sellers`,
    sublabel: "Strict Vetting Process",
    color: "text-emerald-600",
    bg: "bg-emerald-50",
  },
  {
    icon: "lock",
    label: "Secure Checkout",
    sublabel: "Encrypted Transactions",
    color: "text-navy-900",
    bg: "bg-navy-50",
  },
  {
    icon: "credit-card",
    label: "PayMongo · GCash · Stripe",
    sublabel: "Trusted Payment Partners",
    color: "text-brand-500",
    bg: "bg-brand-50",
  },
  {
    icon: "star",
    label: props.stats.average_rating
      ? `${props.stats.average_rating} Average Rating`
      : "Trusted Platform",
    sublabel: props.stats.total_reviews
      ? `From ${formatCount(props.stats.total_reviews)} Reviews`
      : "Growing Community",
    color: "text-amber-500",
    bg: "bg-amber-50",
  },
  {
    icon: "flag",
    label: "Made in the Philippines",
    sublabel: "Supporting Local Talent",
    color: "text-emerald-600",
    bg: "bg-emerald-50",
  },
]);
</script>

<template>
  <section
    class="theme-page-section border-y py-10"
    style="border-color: var(--color-border)"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <!-- Heading -->
      <div class="mb-8 text-center">
        <p
          class="text-xs font-semibold uppercase tracking-widest text-emerald-600"
        >
          Trust & Security
        </p>
        <h3 class="theme-title mt-1 text-lg font-bold">
          Why Filipinos trust NegosyoHub
        </h3>
      </div>

      <!-- Badges grid -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div
          v-for="signal in signals"
          :key="signal.label"
          class="theme-card-muted flex flex-col items-center gap-2 rounded-2xl p-4 text-center transition-all hover:border-emerald-100 hover:bg-emerald-50/30"
        >
          <!-- Icon -->
          <div
            class="flex size-10 items-center justify-center rounded-xl"
            :class="signal.bg"
          >
            <!-- Shield Check -->
            <svg
              v-if="signal.icon === 'shield-check'"
              class="size-5"
              :class="signal.color"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
              />
            </svg>
            <!-- Lock -->
            <svg
              v-else-if="signal.icon === 'lock'"
              class="size-5"
              :class="signal.color"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
              />
            </svg>
            <!-- Credit Card -->
            <svg
              v-else-if="signal.icon === 'credit-card'"
              class="size-5"
              :class="signal.color"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"
              />
            </svg>
            <!-- Star -->
            <svg
              v-else-if="signal.icon === 'star'"
              class="size-5"
              :class="signal.color"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
              />
            </svg>
            <!-- Flag -->
            <span v-else-if="signal.icon === 'flag'" class="text-base">🇵🇭</span>
          </div>

          <div>
            <p class="theme-title text-sm font-semibold">
              {{ signal.label }}
            </p>
            <p class="theme-copy mt-0.5 text-xs">{{ signal.sublabel }}</p>
          </div>
        </div>
      </div>

      <!-- Secure data note -->
      <p
        class="theme-copy mt-6 flex items-center justify-center gap-1.5 text-xs"
      >
        <svg
          class="size-3.5 text-emerald-500"
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
        Your data is protected · 256-bit SSL encryption
      </p>
    </div>
  </section>
</template>
