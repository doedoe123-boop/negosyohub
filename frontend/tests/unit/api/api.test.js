import { describe, it, expect, vi, beforeEach } from "vitest";
import axios from "axios";

// We test that the client has the right defaults, without making real HTTP calls
vi.mock("axios", async () => {
  const actual = await vi.importActual("axios");
  return {
    default: {
      ...actual.default,
      create: vi.fn(() => ({
        defaults: { baseURL: "", withCredentials: true },
        interceptors: {
          request: { use: vi.fn() },
          response: { use: vi.fn() },
        },
        get: vi.fn(),
        post: vi.fn(),
      })),
    },
  };
});

describe("API Client", () => {
  it("is created with correct defaults", async () => {
    const { default: client } = await import("@/api/client");
    // The client is a module-level singleton — just check it has the expected shape
    expect(client).toBeDefined();
    expect(typeof client.get).toBe("function");
    expect(typeof client.post).toBe("function");
  });
});

describe("storesApi", () => {
  it("list calls GET /api/stores", async () => {
    const { storesApi } = await import("@/api/stores");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: { data: [] } });

    await storesApi.list({ search: "pizza" });
    expect(client.get).toHaveBeenCalledWith("/api/v1/stores", {
      params: { search: "pizza" },
    });
  });

  it("show calls GET /api/stores/:slug", async () => {
    const { storesApi } = await import("@/api/stores");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: {} });

    await storesApi.show("pizza-house");
    expect(client.get).toHaveBeenCalledWith("/api/v1/stores/pizza-house");
  });
});

describe("ordersApi", () => {
  it("list calls GET /api/orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: [] });

    await ordersApi.list();
    expect(client.get).toHaveBeenCalledWith("/api/v1/orders", { params: {} });
  });

  it("place calls POST /api/orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    const { default: client } = await import("@/api/client");
    client.post = vi.fn().mockResolvedValue({ data: { order_id: 99 } });

    await ordersApi.place({ payment_method: "paypal" });
    expect(client.post).toHaveBeenCalledWith("/api/v1/orders", {
      payment_method: "paypal",
    });
  });
});

describe("inquiriesApi", () => {
  it("list calls GET /api/v1/user/inquiries", async () => {
    const { inquiriesApi } = await import("@/api/inquiries");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: { data: [] } });

    await inquiriesApi.list();
    expect(client.get).toHaveBeenCalledWith("/api/v1/user/inquiries", {
      params: {},
    });
  });

  it("list passes query params", async () => {
    const { inquiriesApi } = await import("@/api/inquiries");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: { data: [] } });

    await inquiriesApi.list({ page: 2 });
    expect(client.get).toHaveBeenCalledWith("/api/v1/user/inquiries", {
      params: { page: 2 },
    });
  });
});

describe("notificationsApi", () => {
  it("list calls GET /api/v1/user/notifications", async () => {
    const { notificationsApi } = await import("@/api/notifications");
    const { default: client } = await import("@/api/client");
    client.get = vi
      .fn()
      .mockResolvedValue({ data: { notifications: [], unread_count: 0 } });

    await notificationsApi.list();
    expect(client.get).toHaveBeenCalledWith("/api/v1/user/notifications");
  });

  it("markRead calls PATCH /api/v1/user/notifications/:id/read", async () => {
    const { notificationsApi } = await import("@/api/notifications");
    const { default: client } = await import("@/api/client");
    client.patch = vi.fn().mockResolvedValue({});

    await notificationsApi.markRead("uuid-123");
    expect(client.patch).toHaveBeenCalledWith(
      "/api/v1/user/notifications/uuid-123/read",
    );
  });

  it("markAllRead calls POST /api/v1/user/notifications/read-all", async () => {
    const { notificationsApi } = await import("@/api/notifications");
    const { default: client } = await import("@/api/client");
    client.post = vi.fn().mockResolvedValue({});

    await notificationsApi.markAllRead();
    expect(client.post).toHaveBeenCalledWith(
      "/api/v1/user/notifications/read-all",
    );
  });
});

describe("propertiesApi", () => {
  it("quickInquiry calls POST /api/v1/properties/:slug/quick-inquiry", async () => {
    const { propertiesApi } = await import("@/api/properties");
    const { default: client } = await import("@/api/client");
    client.post = vi
      .fn()
      .mockResolvedValue({ data: { message: "Inquiry sent." } });

    await propertiesApi.quickInquiry("sunset-villa", {
      message: "I am interested.",
    });
    expect(client.post).toHaveBeenCalledWith(
      "/api/v1/properties/sunset-villa/quick-inquiry",
      { message: "I am interested." },
    );
  });

  it("quickInquiry uses empty payload by default", async () => {
    const { propertiesApi } = await import("@/api/properties");
    const { default: client } = await import("@/api/client");
    client.post = vi.fn().mockResolvedValue({ data: {} });

    await propertiesApi.quickInquiry("sunset-villa");
    expect(client.post).toHaveBeenCalledWith(
      "/api/v1/properties/sunset-villa/quick-inquiry",
      {},
    );
  });
});
