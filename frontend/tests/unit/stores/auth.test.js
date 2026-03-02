import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useAuthStore } from "@/stores/auth";
import * as authApi from "@/api/auth";

// Mock the API module so no real HTTP calls are made
vi.mock("@/api/auth", () => ({
  authApi: {
    me: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
  },
}));

describe("Auth Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    vi.clearAllMocks();
  });

  it("starts unauthenticated and uninitialized", () => {
    const auth = useAuthStore();
    expect(auth.user).toBeNull();
    expect(auth.isLoggedIn).toBe(false);
    expect(auth.initialized).toBe(false);
  });

  it("fetchUser — sets user on success", async () => {
    authApi.authApi.me.mockResolvedValue({
      data: { id: 1, name: "Juan", role: "customer" },
    });

    const auth = useAuthStore();
    await auth.fetchUser();

    expect(auth.user).toEqual({ id: 1, name: "Juan", role: "customer" });
    expect(auth.isLoggedIn).toBe(true);
    expect(auth.initialized).toBe(true);
  });

  it("fetchUser — stays null on 401", async () => {
    authApi.authApi.me.mockRejectedValue(new Error("Unauthorized"));

    const auth = useAuthStore();
    await auth.fetchUser();

    expect(auth.user).toBeNull();
    expect(auth.isLoggedIn).toBe(false);
    expect(auth.initialized).toBe(true);
  });

  it("login — stores token and sets user", async () => {
    authApi.authApi.login.mockResolvedValue({
      data: {
        token: "tok_123",
        user: { id: 2, name: "Maria", role: "customer" },
      },
    });

    const auth = useAuthStore();
    await auth.login({ email: "maria@example.com", password: "secret" });

    expect(auth.user.name).toBe("Maria");
    expect(localStorage.getItem("api_token")).toBe("tok_123");
  });

  it("logout — clears user and token", async () => {
    authApi.authApi.logout.mockResolvedValue({});
    localStorage.setItem("api_token", "tok_old");

    const auth = useAuthStore();
    auth.user = { id: 1, name: "Juan" };

    await auth.logout();

    expect(auth.user).toBeNull();
    expect(localStorage.getItem("api_token")).toBeNull();
  });

  it("isCustomer — true when role is customer", () => {
    const auth = useAuthStore();
    auth.user = { id: 1, role: "customer" };
    expect(auth.isCustomer).toBe(true);
  });

  it("isCustomer — false when role is store_owner", () => {
    const auth = useAuthStore();
    auth.user = { id: 1, role: "store_owner" };
    expect(auth.isCustomer).toBe(false);
  });
});
