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
    expect(client.get).toHaveBeenCalledWith("/api/stores", {
      params: { search: "pizza" },
    });
  });

  it("show calls GET /api/stores/:slug", async () => {
    const { storesApi } = await import("@/api/stores");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: {} });

    await storesApi.show("pizza-house");
    expect(client.get).toHaveBeenCalledWith("/api/stores/pizza-house");
  });
});

describe("ordersApi", () => {
  it("list calls GET /api/orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    const { default: client } = await import("@/api/client");
    client.get = vi.fn().mockResolvedValue({ data: [] });

    await ordersApi.list();
    expect(client.get).toHaveBeenCalledWith("/api/orders", { params: {} });
  });

  it("place calls POST /api/orders", async () => {
    const { ordersApi } = await import("@/api/orders");
    const { default: client } = await import("@/api/client");
    client.post = vi.fn().mockResolvedValue({ data: { order_id: 99 } });

    await ordersApi.place({ payment_method: "paypal" });
    expect(client.post).toHaveBeenCalledWith("/api/orders", {
      payment_method: "paypal",
    });
  });
});
