<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { TagIcon, FireIcon, SparklesIcon } from "@heroicons/vue/24/outline";
import { promotionsApi } from "@/api/promotions";
import { featuredListingsApi } from "@/api/featuredListings";
import { announcementsApi } from "@/api/announcements";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { sanitizeHtml } from "@/utils/sanitizeHtml";
import { useAppI18n } from "@/i18n";

const promotions = ref([]);
const featuredListings = ref([]);
const announcements = ref([]);
const loading = ref(true);
const { t } = useAppI18n();

const promoTypeStyles = {
  holiday_sale: {
    icon: SparklesIcon,
    badge: "bg-rose-500/12 text-rose-500 ring-rose-500/20",
  },
  seasonal_promotion: {
    icon: TagIcon,
    badge: "bg-emerald-500/12 text-emerald-500 ring-emerald-500/20",
  },
  marketplace_discount: {
    icon: TagIcon,
    badge: "bg-sky-500/12 text-sky-500 ring-sky-500/20",
  },
  flash_sale: {
    icon: FireIcon,
    badge: "bg-amber-500/12 text-amber-500 ring-amber-500/20",
  },
  anniversary: {
    icon: SparklesIcon,
    badge: "bg-indigo-500/12 text-indigo-500 ring-indigo-500/20",
  },
  clearance: {
    icon: TagIcon,
    badge: "bg-slate-500/12 text-slate-400 ring-slate-500/20",
  },
};

const expectationCards = [
  {
    titleKey: "marketing.deals.flashSalesTitle",
    descriptionKey: "marketing.deals.flashSalesDescription",
    accent: "from-amber-500/25 to-orange-500/10",
  },
  {
    titleKey: "marketing.deals.marketplaceDiscountsTitle",
    descriptionKey: "marketing.deals.marketplaceDiscountsDescription",
    accent: "from-emerald-500/25 to-teal-500/10",
  },
  {
    titleKey: "marketing.deals.featuredFindsTitle",
    descriptionKey: "marketing.deals.featuredFindsDescription",
    accent: "from-sky-500/25 to-blue-500/10",
  },
  {
    titleKey: "marketing.deals.seasonalCampaignsTitle",
    descriptionKey: "marketing.deals.seasonalCampaignsDescription",
    accent: "from-rose-500/25 to-fuchsia-500/10",
  },
];

useSeoMeta(() => ({
  title: `${t("marketing.deals.titleLead")} ${t("marketing.deals.titleAccent")}`,
  description: t("marketing.deals.subtitle"),
  ogType: "website",
}));

const featuredCards = computed(() =>
  featuredListings.value.map((listing) => {
    const item = listing.item ?? {};
    const featuredType = listing.featured_type;

    if (featuredType === "store") {
      return {
        id: listing.id,
        title: item.name ?? t("marketing.deals.featuredStoreTitle"),
        subtitle:
          item.sector_label ??
          item.tagline ??
          t("marketing.deals.featuredStoreSubtitle"),
        price: null,
        image: item.logo_url ?? item.banner_url ?? null,
        link: item.slug ? `/stores/${item.slug}` : "/stores",
        badge: t("marketing.deals.badgeStore"),
      };
    }

    if (featuredType === "product") {
      return {
        id: listing.id,
        title: item.name ?? t("marketing.deals.featuredProductTitle"),
        subtitle:
          item.store?.name ?? t("marketing.deals.featuredProductSubtitle"),
        price:
          item.price != null
            ? `₱${parseFloat(item.price).toLocaleString("en-PH", { maximumFractionDigits: 0 })}`
            : null,
        image: item.thumbnail ?? item.image ?? null,
        link: item.id ? `/products/${item.id}` : "/stores",
        badge: t("marketing.deals.badgeProduct"),
      };
    }

    return {
      id: listing.id,
      title: item.name ?? t("marketing.deals.featuredServiceTitle"),
      subtitle:
        item.city ?? item.tagline ?? t("marketing.deals.featuredServiceSubtitle"),
      price: null,
      image: item.logo_url ?? item.banner_url ?? null,
      link: item.slug ? `/movers/${item.slug}` : "/movers",
      badge: t("marketing.deals.badgeService"),
    };
  }),
);

function promotionLabel(promotion) {
  if (promotion.discount_percentage) {
    return t("marketing.deals.percentOff", {
      value: promotion.discount_percentage,
    });
  }

  if (promotion.discount_amount_cents) {
    return t("marketing.deals.amountOff", {
      value: (promotion.discount_amount_cents / 100).toLocaleString("en-PH"),
    });
  }

  return t("marketing.deals.activeOffer");
}

function urgencyLabel(endsAt) {
  if (!endsAt) {
    return t("marketing.deals.urgencyOngoing");
  }

  const diff = Math.ceil((new Date(endsAt) - new Date()) / 86400000);

  if (diff <= 0) {
    return t("marketing.deals.urgencyEndingSoon");
  }

  if (diff === 1) {
    return t("marketing.deals.urgencyTomorrow");
  }

  return t("marketing.deals.urgencyDaysLeft", { days: diff });
}

function sanitizedAnnouncement(content) {
  return sanitizeHtml(content);
}

onMounted(async () => {
  try {
    const [promotionsResponse, featuredResponse, announcementsResponse] =
      await Promise.all([
        promotionsApi.list({ limit: 8 }),
        featuredListingsApi.list({ limit: 6 }),
        announcementsApi.list({ limit: 3 }),
      ]);

    promotions.value = promotionsResponse.data ?? [];
    featuredListings.value = featuredResponse.data ?? [];
    announcements.value = announcementsResponse.data ?? [];
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="theme-page">
    <section class="theme-page-section border-b theme-divider-soft">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
          <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">
              {{ t("marketing.deals.eyebrow") }}
            </p>
            <h1 class="max-w-3xl text-4xl font-black tracking-tight text-[var(--color-text)] sm:text-5xl">
              {{ t("marketing.deals.titleLead") }}
              <span class="text-brand-500">{{ t("marketing.deals.titleAccent") }}</span>
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 theme-copy sm:text-lg">
              {{ t("marketing.deals.subtitle") }}
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              <RouterLink to="/stores" class="btn-primary rounded-xl px-5 py-3 text-sm font-bold">
                {{ t("marketing.deals.browseStores") }}
              </RouterLink>
              <RouterLink to="/insights" class="btn-secondary rounded-xl px-5 py-3 text-sm font-bold">
                {{ t("marketing.deals.viewInsights") }}
              </RouterLink>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div
              v-for="card in expectationCards.slice(0, 2)"
              :key="card.title"
              class="theme-card relative overflow-hidden rounded-3xl p-5"
            >
              <div class="absolute inset-0 bg-gradient-to-br opacity-80" :class="card.accent" />
              <div class="relative">
                <p class="theme-title text-lg font-bold">{{ t(card.titleKey) }}</p>
                <p class="mt-2 text-sm leading-6 theme-copy">{{ t(card.descriptionKey) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="theme-page-section">
      <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div
          v-if="announcements.length"
          class="grid gap-4 md:grid-cols-3"
        >
          <article
            v-for="announcement in announcements"
            :key="announcement.id"
            class="theme-card rounded-2xl p-5"
          >
            <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-brand-500">
              {{ announcement.type ?? t("marketing.deals.announcementDefaultType") }}
            </p>
            <h2 class="mt-2 text-lg font-bold theme-title">
              {{ announcement.title }}
            </h2>
            <p
              class="mt-2 text-sm leading-6 theme-copy"
              v-html="sanitizedAnnouncement(announcement.content)"
            />
          </article>
        </div>

        <div class="mt-12 flex items-end justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-500">
              {{ t("marketing.deals.activePromotionsEyebrow") }}
            </p>
            <h2 class="mt-2 text-2xl font-bold theme-title">{{ t("marketing.deals.activePromotionsTitle") }}</h2>
          </div>
          <RouterLink to="/stores" class="text-sm font-semibold text-brand-500 hover:text-brand-400">
            {{ t("marketing.deals.shopMarketplace") }} →
          </RouterLink>
        </div>

        <div v-if="loading" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div v-for="index in 4" :key="index" class="theme-card animate-pulse rounded-2xl p-6">
            <div class="h-5 w-24 rounded bg-[var(--color-surface-muted)]" />
            <div class="mt-4 h-6 w-2/3 rounded bg-[var(--color-surface-muted)]" />
            <div class="mt-3 h-4 w-full rounded bg-[var(--color-surface-muted)]" />
            <div class="mt-2 h-4 w-3/4 rounded bg-[var(--color-surface-muted)]" />
          </div>
        </div>

        <div v-else-if="promotions.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="promotion in promotions"
            :key="promotion.id"
            class="theme-card theme-card-hover rounded-2xl p-6"
          >
            <div class="flex items-start justify-between gap-3">
              <span
                class="inline-flex size-11 items-center justify-center rounded-2xl ring-1"
                :class="(promoTypeStyles[promotion.type] ?? promoTypeStyles.clearance).badge"
              >
                <component
                  :is="(promoTypeStyles[promotion.type] ?? promoTypeStyles.clearance).icon"
                  class="size-5"
                />
              </span>
              <span class="rounded-full bg-brand-500/12 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-500">
                {{ urgencyLabel(promotion.ends_at) }}
              </span>
            </div>

            <p class="mt-5 text-2xl font-black text-brand-500">
              {{ promotionLabel(promotion) }}
            </p>
            <h3 class="mt-2 text-lg font-bold theme-title">
              {{ promotion.name }}
            </h3>
            <p class="mt-2 text-sm leading-6 theme-copy">
              {{ promotion.description || t("marketing.deals.defaultPromoDescription") }}
            </p>
          </article>
        </div>

        <div v-else class="theme-empty-state mt-6 rounded-2xl py-12 text-center">
          <p class="text-base font-semibold theme-title">{{ t("marketing.deals.emptyTitle") }}</p>
          <p class="mt-2 text-sm theme-copy">
            {{ t("marketing.deals.emptyBody") }}
          </p>
        </div>

        <div class="mt-14 flex items-end justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-sky-500">
              {{ t("marketing.deals.curatedPicksEyebrow") }}
            </p>
            <h2 class="mt-2 text-2xl font-bold theme-title">{{ t("marketing.deals.curatedPicksTitle") }}</h2>
          </div>
        </div>

        <div v-if="featuredCards.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <RouterLink
            v-for="card in featuredCards"
            :key="card.id"
            :to="card.link"
            class="theme-card theme-card-hover overflow-hidden rounded-2xl"
          >
            <div
              class="aspect-[16/9] w-full"
              :style="
                card.image
                  ? `background-image: linear-gradient(rgba(15,32,68,0.2), rgba(15,32,68,0.6)), url('${card.image}'); background-size: cover; background-position: center;`
                  : 'background: linear-gradient(135deg, rgba(15,32,68,0.95), rgba(39,96,180,0.75));'
              "
            />
            <div class="p-5">
              <span class="rounded-full bg-emerald-500/12 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-500">
                {{ card.badge }}
              </span>
              <h3 class="mt-3 text-lg font-bold theme-title">{{ card.title }}</h3>
              <p class="mt-2 text-sm leading-6 theme-copy">{{ card.subtitle }}</p>
              <p v-if="card.price" class="mt-4 text-xl font-black text-brand-500">{{ card.price }}</p>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
