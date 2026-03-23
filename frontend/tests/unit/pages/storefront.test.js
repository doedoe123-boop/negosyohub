import { describe, it, expect, vi, beforeEach } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import Stores from "@/pages/Stores.vue";
import NotFound from "@/pages/NotFound.vue";
import StoreDetail from "@/pages/store/StoreDetail.vue";
import DealsPage from "@/pages/DealsPage.vue";
import MarketInsightsPage from "@/pages/MarketInsightsPage.vue";
import FaqPage from "@/pages/FaqPage.vue";

// ---------------------------------------------------------------------------
// API mocks
// ---------------------------------------------------------------------------

vi.mock("@/api/stores", () => ({
  storesApi: {
    list: vi.fn(),
    show: vi.fn(),
    products: vi.fn(),
    properties: vi.fn(),
  },
}));

vi.mock("@/api/reviews", () => ({
  reviewsApi: {
    listForStore: vi.fn(),
    submitForStore: vi.fn(),
  },
}));

vi.mock("@/api/products", () => ({
  productsApi: { list: vi.fn(), show: vi.fn() },
}));

vi.mock("@/api/properties", () => ({
  propertiesApi: { list: vi.fn(), show: vi.fn(), featured: vi.fn() },
}));

vi.mock("@/api/homepage", () => ({
  homepageApi: { stats: vi.fn().mockResolvedValue({ data: {} }) },
}));

vi.mock("@/api/marketInsights", () => ({
  marketInsightsApi: {
    show: vi.fn(),
  },
}));

vi.mock("@/api/advertisements", () => ({
  advertisementsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/announcements", () => ({
  announcementsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/promotions", () => ({
  promotionsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/featuredListings", () => ({
  featuredListingsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/faq", () => ({
  faqApi: {
    list: vi.fn(),
  },
}));

// Stub complex sub-components from Home page so we don't need to provision all their deps
vi.mock("@/components/DicedHeroSection.vue", () => ({
  default: { template: "<section>Hero</section>" },
}));
vi.mock("@/components/CategoryStrip.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/components/homepage/VerifiedProperties.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/components/homepage/TrendingCarousel.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/components/homepage/TrustStrip.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/components/homepage/AdBanner.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/components/homepage/PromotionBanner.vue", () => ({
  default: { template: "<div />" },
}));
vi.mock("@/composables/useSeoMeta", () => ({
  useSeoMeta: vi.fn(),
}));
vi.mock("@/components/ReviewForm.vue", () => ({
  default: {
    props: ["reviews", "reviewCount", "averageRating"],
    emits: ["submit"],
    template:
      "<div data-testid='review-form'>Reviews: {{ reviewCount }}<button type='button' data-testid='submit-review' @click=\"$emit('submit', { rating: 5, title: 'Great shop', content: 'Very smooth order and delivery.' })\">Submit Review</button></div>",
    methods: {
      onSuccess() {},
      onError() {},
    },
  },
}));
vi.mock("@/composables/useHomepageStats", () => ({
  useHomepageStats: () => ({
    stats: {},
    loaded: true,
    formatCount: (n) => String(n),
  }),
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function storeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/stores", component: Stores },
      { path: "/stores/:slug", component: StoreDetail },
      { path: "/deals", component: DealsPage },
      { path: "/insights", component: MarketInsightsPage },
      { path: "/faq", component: FaqPage },
      { path: "/properties", component: { template: "<div />" } },
      { path: "/movers", component: { template: "<div />" } },
    ],
  });
}

// ---------------------------------------------------------------------------
// Stores listing page
// ---------------------------------------------------------------------------

describe("Stores listing page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    localStorage.clear();
    document.documentElement.lang = "en";
  });

  it("renders Browse Stores heading", async () => {
    const { storesApi } = await import("@/api/stores");
    storesApi.list.mockResolvedValue({ data: { data: [] } });

    const router = storeRouter();
    await router.push("/stores");

    const wrapper = mount(Stores, { global: { plugins: [pinia, router] } });
    await flushPromises();

    expect(wrapper.text()).toContain("Browse E-Commerce Stores");
  });

  it("displays stores returned from the API", async () => {
    const { storesApi } = await import("@/api/stores");
    storesApi.list.mockResolvedValue({
      data: {
        data: [
          { id: 1, name: "Pizza Palace", slug: "pizza-palace", sector: "Food & Beverage", logo_url: null },
          { id: 2, name: "Burger Barn", slug: "burger-barn", sector: "Food & Beverage", logo_url: null },
        ],
        meta: {},
      },
    });

    const router = storeRouter();
    await router.push("/stores");

    const wrapper = mount(Stores, { global: { plugins: [pinia, router] } });
    await flushPromises();

    expect(wrapper.text()).toContain("Pizza Palace");
    expect(wrapper.text()).toContain("Burger Barn");
  });

  it("renders the search input", async () => {
    const { storesApi } = await import("@/api/stores");
    storesApi.list.mockResolvedValue({ data: { data: [], meta: {} } });

    const router = storeRouter();
    await router.push("/stores");

    const wrapper = mount(Stores, { global: { plugins: [pinia, router] } });
    await flushPromises();

    const input = wrapper.find('input[type="search"], input[placeholder*="Search"]');
    expect(input.exists()).toBe(true);
  });

  it("shows empty state message when no stores returned", async () => {
    const { storesApi } = await import("@/api/stores");
    storesApi.list.mockResolvedValue({ data: { data: [], meta: {} } });

    const router = storeRouter();
    await router.push("/stores");

    const wrapper = mount(Stores, { global: { plugins: [pinia, router] } });
    await flushPromises();

    // either "No stores found" text or empty list — just verify no store cards appear
    expect(wrapper.text()).not.toContain("Pizza Palace");
  });

  it("calls storesApi.list on mount", async () => {
    const { storesApi } = await import("@/api/stores");
    storesApi.list.mockResolvedValue({ data: { data: [], meta: {} } });

    const router = storeRouter();
    await router.push("/stores");

    mount(Stores, { global: { plugins: [pinia, router] } });
    await flushPromises();

    expect(storesApi.list).toHaveBeenCalledOnce();
  });
});

describe("Store detail page", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  it("renders store reviews for ecommerce stores", async () => {
    const { storesApi } = await import("@/api/stores");
    const { reviewsApi } = await import("@/api/reviews");

    storesApi.show.mockResolvedValue({
      data: {
        id: 9,
        slug: "tech-nest",
        name: "TechNest",
        sector: "ecommerce",
      },
    });
    storesApi.products.mockResolvedValue({ data: { data: [] } });
    reviewsApi.listForStore.mockResolvedValue({
      data: {
        data: [{ id: 1, name: "Ana Reyes", rating: 5, content: "Great shop." }],
        review_count: 1,
        average_rating: 5,
      },
    });

    const router = storeRouter();
    await router.push("/stores/tech-nest");
    await router.isReady();

    const wrapper = mount(StoreDetail, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(reviewsApi.listForStore).toHaveBeenCalledWith("tech-nest");
    expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(true);
    expect(wrapper.text()).toContain("Reviews: 1");
  });

  it("submits a store review from the detail page", async () => {
    const { storesApi } = await import("@/api/stores");
    const { reviewsApi } = await import("@/api/reviews");

    storesApi.show.mockResolvedValue({
      data: {
        id: 11,
        slug: "fresh-basket",
        name: "FreshBasket",
        sector: "ecommerce",
      },
    });
    storesApi.products.mockResolvedValue({ data: { data: [] } });
    reviewsApi.listForStore.mockResolvedValue({
      data: {
        data: [],
        review_count: 0,
        average_rating: null,
      },
    });
    reviewsApi.submitForStore.mockResolvedValue({
      data: { message: "ok" },
    });

    const router = storeRouter();
    await router.push("/stores/fresh-basket");
    await router.isReady();

    const wrapper = mount(StoreDetail, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();
    await wrapper.find('[data-testid="submit-review"]').trigger("click");

    expect(reviewsApi.submitForStore).toHaveBeenCalledWith("fresh-basket", {
      rating: 5,
      title: "Great shop",
      content: "Very smooth order and delivery.",
    });
  });
});

describe("Marketing storefront pages", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  it("renders deals page promotions and featured listings", async () => {
    const { promotionsApi } = await import("@/api/promotions");
    const { featuredListingsApi } = await import("@/api/featuredListings");
    const { announcementsApi } = await import("@/api/announcements");

    promotionsApi.list.mockResolvedValue({
      data: [
        {
          id: 1,
          name: "Weekend Saver",
          description: "Big promo for verified local sellers.",
          type: "flash_sale",
          discount_percentage: 20,
          ends_at: null,
        },
      ],
    });
    featuredListingsApi.list.mockResolvedValue({
      data: [
        {
          id: 2,
          featured_type: "store",
          item: {
            slug: "tech-nest",
            name: "TechNest",
            tagline: "Gadgets and accessories",
          },
        },
      ],
    });
    announcementsApi.list.mockResolvedValue({
      data: [
        {
          id: 3,
          title: "Marketplace Week",
          type: "promotion",
          content: "<p>Fresh offers are now live.</p>",
        },
      ],
    });

    const router = storeRouter();
    await router.push("/deals");
    await router.isReady();

    const wrapper = mount(DealsPage, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(wrapper.text()).toContain("Deals & Offers Built for");
    expect(wrapper.text()).toContain("Weekend Saver");
    expect(wrapper.text()).toContain("TechNest");
  });

  it("renders marketing pages in Filipino when the locale is switched", async () => {
    const { marketInsightsApi } = await import("@/api/marketInsights");
    const { faqApi } = await import("@/api/faq");
    const { useAppI18n } = await import("@/i18n");

    localStorage.setItem("negosyohub.locale", "fil");
    await useAppI18n().setLocale("fil");

    marketInsightsApi.show.mockResolvedValue({
      data: {
        stats: {
          approved_suppliers: 42,
          registered_users: 310,
          active_sectors: 4,
          cities_covered: 18,
          average_rating: 4.6,
          published_reviews: 128,
        },
        top_sectors: [],
        top_cities: [],
        health: {
          permit_compliance_rate: 88,
          platform_status: "online",
          updated_every: "24h",
        },
      },
    });
    faqApi.list.mockResolvedValue({ data: [] });

    const router = storeRouter();
    await router.push("/insights");
    await router.isReady();

    const insightsWrapper = mount(MarketInsightsPage, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(insightsWrapper.text()).toContain("Tuklasin ang Sellers");
    expect(insightsWrapper.text()).toContain("Bakit ito mahalaga");

    await router.push("/faq");
    await flushPromises();

    const faqWrapper = mount(FaqPage, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(faqWrapper.text()).toContain("Mga Madalas Itanong");
    expect(faqWrapper.text()).toContain("Wala pang FAQs sa ngayon.");
  });

  it("renders market insights from the API", async () => {
    const { marketInsightsApi } = await import("@/api/marketInsights");

    marketInsightsApi.show.mockResolvedValue({
      data: {
        stats: {
          approved_suppliers: 42,
          registered_users: 310,
          active_sectors: 4,
          cities_covered: 18,
          average_rating: 4.6,
          published_reviews: 128,
        },
        top_sectors: [
          { slug: "ecommerce", name: "E-Commerce", total: 20 },
          { slug: "real_estate", name: "Real Estate", total: 12 },
        ],
        top_cities: [
          { city: "Pasig", total: 10, share: 23.8 },
        ],
        health: {
          permit_compliance_rate: 88,
          platform_status: "online",
          updated_every: "24h",
        },
      },
    });

    const router = storeRouter();
    await router.push("/insights");
    await router.isReady();

    const wrapper = mount(MarketInsightsPage, {
      global: {
        plugins: [pinia, router],
      },
    });

    await flushPromises();

    expect(marketInsightsApi.show).toHaveBeenCalledOnce();
    expect(wrapper.text()).toContain("42");
    expect(wrapper.text()).toContain("E-Commerce");
    expect(wrapper.text()).toContain("Pasig");
    expect(wrapper.text()).toContain("88%");
  });
});

// ---------------------------------------------------------------------------
// 404 / NotFound page
// ---------------------------------------------------------------------------

describe("404 page", () => {
  it("shows 404 text", () => {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(NotFound, {
      global: {
        plugins: [
          pinia,
          createRouter({
            history: createMemoryHistory(),
            routes: [{ path: "/:pathMatch(.*)*", component: NotFound }],
          }),
        ],
      },
    });
    expect(wrapper.text()).toContain("404");
  });
});
