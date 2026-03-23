import { beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("@/api/localization", () => ({
  localizationApi: {
    catalog: vi.fn(),
  },
}));

describe("app i18n", () => {
  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    localStorage.clear();
    document.documentElement.lang = "en";
  });

  it("uses backend catalog overrides and persists the selected locale", async () => {
    const { localizationApi } = await import("@/api/localization");
    localizationApi.catalog.mockResolvedValue({
      data: {
        available_locales: [
          { code: "en", name: "English", is_default: true },
          { code: "fil", name: "Filipino", is_default: false },
        ],
        messages: {
          checkout: {
            orderSummary: "Custom Checkout Summary",
          },
        },
      },
    });

    const { createAppI18n, STORAGE_KEY } = await import("@/i18n");
    const i18n = createAppI18n();

    await i18n.setLocale("fil");

    expect(localizationApi.catalog).toHaveBeenCalledWith("fil");
    expect(i18n.t("checkout.orderSummary")).toBe("Custom Checkout Summary");
    expect(localStorage.getItem(STORAGE_KEY)).toBe("fil");
    expect(document.documentElement.lang).toBe("fil");
  });

  it("falls back to the English catalog when the active locale is missing a key", async () => {
    const { localizationApi } = await import("@/api/localization");
    localizationApi.catalog.mockRejectedValue(new Error("offline"));

    const { createAppI18n } = await import("@/i18n");
    const i18n = createAppI18n();

    await i18n.setLocale("fil");

    expect(i18n.t("nav.signIn")).toBe("Mag-sign in");
    expect(i18n.t("orders.view")).toBe("Tingnan");
    expect(i18n.t("checkout.continueCheckout")).toBe("Magpatuloy sa Checkout");
    expect(i18n.t("missing.key", {}, "Fallback copy")).toBe("Fallback copy");
  });

  it("interpolates replacement values inside translated strings", async () => {
    const { localizationApi } = await import("@/api/localization");
    localizationApi.catalog.mockRejectedValue(new Error("offline"));

    const { createAppI18n } = await import("@/i18n");
    const i18n = createAppI18n();

    await i18n.setLocale("en");

    expect(
      i18n.t("auth.forgotPassword.success", { email: "buyer@example.com" }),
    ).toContain("buyer@example.com");
  });
});
