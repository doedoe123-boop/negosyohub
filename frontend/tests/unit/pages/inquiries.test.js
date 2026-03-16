import { describe, it, expect, vi, beforeEach } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import InquiriesPage from "@/pages/account/InquiriesPage.vue";

// ---------------------------------------------------------------------------
// API mocks
// ---------------------------------------------------------------------------

vi.mock("@/api/inquiries", () => ({
  inquiriesApi: { list: vi.fn() },
}));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const mockUser = {
  id: 1,
  name: "Juan dela Cruz",
  email: "juan@example.com",
  phone: "09171234567",
  role: "customer",
  notification_preferences: { order_updates: true, promotions: false },
};

const mockInquiries = [
  {
    id: 10,
    status: "new",
    status_label: "New",
    message: "I'm interested in this property.",
    viewing_date: null,
    created_at: "2026-03-01T09:00:00.000Z",
    property: {
      id: 1,
      title: "Sunset Villa",
      slug: "sunset-villa",
      city: "Makati",
      formatted_price: "₱15,000/mo",
      featured_image: null,
    },
    store: {
      name: "Golden Gate Realty",
      slug: "golden-gate-realty",
    },
  },
  {
    id: 11,
    status: "viewing_scheduled",
    status_label: "Viewing Scheduled",
    message: "When can I schedule a viewing?",
    viewing_date: "2026-03-20",
    created_at: "2026-03-05T10:00:00.000Z",
    property: {
      id: 2,
      title: "BGC Condo Unit",
      slug: "bgc-condo-unit",
      city: "Taguig",
      formatted_price: "₱25,000/mo",
      featured_image: "https://example.com/photo.jpg",
    },
    store: {
      name: "Metro Properties",
      slug: "metro-properties",
    },
  },
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/account/inquiries", component: InquiriesPage },
      { path: "/properties/:slug", component: { template: "<div />" } },
      { path: "/properties", component: { template: "<div />" } },
    ],
  });
}

function seedAuth(pinia) {
  setActivePinia(pinia);
  const auth = useAuthStore();
  auth.user = { ...mockUser };
  auth.initialized = true;
  return auth;
}

function mountPage(pinia) {
  const router = buildRouter();
  return {
    wrapper: mount(InquiriesPage, { global: { plugins: [pinia, router] } }),
    router,
  };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("InquiriesPage", () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    vi.clearAllMocks();
  });

  it("shows the page heading", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("My Inquiries");
  });

  it("shows inquiry property titles and store names", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Sunset Villa");
    expect(wrapper.text()).toContain("Golden Gate Realty");
    expect(wrapper.text()).toContain("BGC Condo Unit");
    expect(wrapper.text()).toContain("Metro Properties");
  });

  it("shows status labels for each inquiry", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("New");
    expect(wrapper.text()).toContain("Viewing Scheduled");
  });

  it("shows the viewing date when scheduled", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("2026-03-20");
  });

  it("shows city for inquiries that have one", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Makati");
    expect(wrapper.text()).toContain("Taguig");
  });

  it("shows empty state when there are no inquiries", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: [] } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("No inquiries yet");
  });

  it("shows error message when API fails", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockRejectedValue(new Error("Network error"));

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("Failed to load inquiries");
  });

  it("renders property image when available", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    const img = wrapper.find("img");
    expect(img.exists()).toBe(true);
    expect(img.attributes("src")).toBe("https://example.com/photo.jpg");
  });

  it("shows formatted price for the property", async () => {
    seedAuth(pinia);
    const { inquiriesApi } = await import("@/api/inquiries");
    inquiriesApi.list.mockResolvedValue({ data: { data: mockInquiries } });

    const { wrapper } = mountPage(pinia);
    await flushPromises();

    expect(wrapper.text()).toContain("₱15,000/mo");
    expect(wrapper.text()).toContain("₱25,000/mo");
  });
});
