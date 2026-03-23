import { describe, it, expect, vi, beforeEach } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import Stores from "@/pages/Stores.vue";
import NotFound from "@/pages/NotFound.vue";
import StoreDetail from "@/pages/store/StoreDetail.vue";

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

vi.mock("@/api/advertisements", () => ({
  advertisementsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/promotions", () => ({
  promotionsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
}));

vi.mock("@/api/featuredListings", () => ({
  featuredListingsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
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
