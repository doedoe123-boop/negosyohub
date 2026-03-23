<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import {
  BuildingStorefrontIcon,
  UserGroupIcon,
  Squares2X2Icon,
  MapPinIcon,
  ShieldCheckIcon,
  ChartBarIcon,
} from "@heroicons/vue/24/outline";
import { marketInsightsApi } from "@/api/marketInsights";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { useAppI18n } from "@/i18n";

const loading = ref(true);
const { t } = useAppI18n();
const insights = ref({
  stats: {
    approved_suppliers: 0,
    registered_users: 0,
    active_sectors: 0,
    cities_covered: 0,
    average_rating: 0,
    published_reviews: 0,
  },
  top_sectors: [],
  top_cities: [],
  health: {
    permit_compliance_rate: 0,
    platform_status: "online",
    updated_every: "24h",
  },
});

useSeoMeta(() => ({
  title: `${t("marketing.insights.titleLead")} ${t("marketing.insights.titleAccent")}`,
  description: t("marketing.insights.subtitle"),
  ogType: "website",
}));

const statCards = computed(() => [
  {
    label: t("marketing.insights.verifiedSuppliers"),
    value: insights.value.stats.approved_suppliers,
    icon: BuildingStorefrontIcon,
    accent: "text-sky-500 bg-sky-500/12",
  },
  {
    label: t("marketing.insights.registeredUsers"),
    value: insights.value.stats.registered_users,
    icon: UserGroupIcon,
    accent: "text-emerald-500 bg-emerald-500/12",
  },
  {
    label: t("marketing.insights.activeSectors"),
    value: insights.value.stats.active_sectors,
    icon: Squares2X2Icon,
    accent: "text-amber-500 bg-amber-500/12",
  },
  {
    label: t("marketing.insights.citiesCovered"),
    value: insights.value.stats.cities_covered,
    icon: MapPinIcon,
    accent: "text-violet-500 bg-violet-500/12",
  },
]);

const maxSectorCount = computed(() =>
  Math.max(...insights.value.top_sectors.map((sector) => sector.total), 1),
);

onMounted(async () => {
  try {
    const { data } = await marketInsightsApi.show();
    insights.value = data;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="theme-page">
    <section class="theme-page-section border-b theme-divider-soft">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        <div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr]">
          <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-sky-500">
              {{ t("marketing.insights.eyebrow") }}
            </p>
            <h1 class="max-w-3xl text-4xl font-black tracking-tight text-[var(--color-text)] sm:text-5xl">
              {{ t("marketing.insights.titleLead") }}
              <span class="text-sky-500">{{ t("marketing.insights.titleAccent") }}</span>
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 theme-copy sm:text-lg">
              {{ t("marketing.insights.subtitle") }}
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              <RouterLink to="/stores" class="btn-primary rounded-xl px-5 py-3 text-sm font-bold">
                {{ t("marketing.insights.exploreSellers") }}
              </RouterLink>
              <RouterLink to="/deals" class="btn-secondary rounded-xl px-5 py-3 text-sm font-bold">
                {{ t("marketing.insights.browseDeals") }}
              </RouterLink>
            </div>
          </div>

          <div class="theme-card rounded-3xl p-6">
            <div class="flex items-center gap-3">
              <div class="flex size-12 items-center justify-center rounded-2xl bg-violet-500/12 text-violet-500">
                <ChartBarIcon class="size-6" />
              </div>
              <div>
                <p class="text-sm font-semibold theme-copy">{{ t("marketing.insights.platformHealth") }}</p>
                <h2 class="text-xl font-bold theme-title">{{ t("marketing.insights.snapshotTitle") }}</h2>
              </div>
            </div>
            <dl class="mt-6 space-y-5">
              <div class="flex items-center justify-between">
                <dt class="text-sm theme-copy">{{ t("marketing.insights.permitCompliance") }}</dt>
                <dd class="text-lg font-black text-emerald-500">
                  {{ insights.health.permit_compliance_rate }}%
                </dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm theme-copy">{{ t("marketing.insights.publishedReviews") }}</dt>
                <dd class="text-lg font-black text-brand-500">
                  {{ insights.stats.published_reviews }}
                </dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm theme-copy">{{ t("marketing.insights.averageRating") }}</dt>
                <dd class="text-lg font-black text-amber-500">
                  {{ insights.stats.average_rating || "—" }}
                </dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm theme-copy">{{ t("marketing.insights.status") }}</dt>
                <dd class="inline-flex items-center gap-2 rounded-full bg-emerald-500/12 px-3 py-1 text-sm font-semibold uppercase tracking-wide text-emerald-500">
                  <span class="size-2 rounded-full bg-emerald-500" />
                  {{ insights.health.platform_status }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </section>

    <section class="theme-page-section">
      <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div v-for="index in 4" :key="index" class="theme-card animate-pulse rounded-2xl p-6">
            <div class="h-11 w-11 rounded-2xl bg-[var(--color-surface-muted)]" />
            <div class="mt-5 h-7 w-24 rounded bg-[var(--color-surface-muted)]" />
            <div class="mt-3 h-4 w-32 rounded bg-[var(--color-surface-muted)]" />
          </div>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="card in statCards"
            :key="card.label"
            class="theme-card rounded-2xl p-6"
          >
            <div class="flex size-12 items-center justify-center rounded-2xl" :class="card.accent">
              <component :is="card.icon" class="size-6" />
            </div>
            <p class="mt-5 text-3xl font-black theme-title">{{ card.value }}</p>
            <p class="mt-2 text-sm font-semibold theme-copy">{{ card.label }}</p>
          </article>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-[1.5fr_0.9fr]">
          <section class="theme-card rounded-3xl p-7">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-sky-500">
                  {{ t("marketing.insights.sectorPulseEyebrow") }}
                </p>
                <h2 class="mt-2 text-2xl font-bold theme-title">{{ t("marketing.insights.sectorPulseTitle") }}</h2>
              </div>
              <p class="text-xs font-semibold uppercase tracking-[0.25em] theme-copy">
                {{ t("marketing.insights.updatedEvery", { interval: insights.health.updated_every }) }}
              </p>
            </div>

            <div v-if="insights.top_sectors.length" class="mt-8 space-y-5">
              <div v-for="sector in insights.top_sectors" :key="sector.slug">
                <div class="mb-2 flex items-center justify-between gap-3">
                  <span class="text-sm font-semibold theme-title">{{ sector.name }}</span>
                  <span class="text-xs font-semibold uppercase tracking-wide theme-copy">
                    {{ sector.total }} {{ t("marketing.insights.storesSuffix") }}
                  </span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-[var(--color-surface-muted)]">
                  <div
                    class="h-full rounded-full bg-gradient-to-r from-sky-500 to-brand-500"
                    :style="{ width: `${Math.max((sector.total / maxSectorCount) * 100, 10)}%` }"
                  />
                </div>
              </div>
            </div>
          </section>

          <section class="space-y-8">
            <article class="theme-card rounded-3xl p-7">
              <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-amber-500/12 text-amber-500">
                  <MapPinIcon class="size-5" />
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-500">
                    {{ t("marketing.insights.geographicReachEyebrow") }}
                  </p>
                  <h2 class="mt-1 text-xl font-bold theme-title">{{ t("marketing.insights.geographicReachTitle") }}</h2>
                </div>
              </div>

              <div v-if="insights.top_cities.length" class="mt-6 space-y-4">
                <div
                  v-for="city in insights.top_cities"
                  :key="city.city"
                  class="flex items-center justify-between gap-3"
                >
                  <div>
                    <p class="text-sm font-semibold theme-title">{{ city.city }}</p>
                    <p class="text-xs theme-copy">{{ t("marketing.insights.approvedStores", { count: city.total }) }}</p>
                  </div>
                  <span class="rounded-full bg-amber-500/12 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-500">
                    {{ city.share }}%
                  </span>
                </div>
              </div>
            </article>

            <article class="theme-card rounded-3xl p-7">
              <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-500">
                  <ShieldCheckIcon class="size-5" />
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-500">
                    {{ t("marketing.insights.trustLayerEyebrow") }}
                  </p>
                  <h2 class="mt-1 text-xl font-bold theme-title">{{ t("marketing.insights.trustLayerTitle") }}</h2>
                </div>
              </div>
              <p class="mt-6 text-sm leading-7 theme-copy">
                {{ t("marketing.insights.trustLayerBody") }}
              </p>
            </article>
          </section>
        </div>
      </div>
    </section>
  </div>
</template>
