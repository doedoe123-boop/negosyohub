import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { nextTick } from "vue";

function mockMatchMedia(matches = false) {
  return vi.fn().mockImplementation(() => ({
    matches,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
  }));
}

describe("useTheme", () => {
  beforeEach(() => {
    vi.resetModules();
    localStorage.clear();
    document.documentElement.className = "";
    document.documentElement.removeAttribute("data-theme");
    document.documentElement.style.colorScheme = "";
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("hydrates the stored dark theme onto the document root", async () => {
    localStorage.setItem("negosyohub-theme", "dark");
    vi.stubGlobal("matchMedia", mockMatchMedia(false));

    const { useTheme } = await import("@/composables/useTheme");
    const theme = useTheme();

    expect(theme.theme.value).toBe("dark");
    expect(theme.isDark.value).toBe(true);
    expect(document.documentElement.classList.contains("dark")).toBe(true);
    expect(document.documentElement.dataset.theme).toBe("dark");
    expect(document.documentElement.style.colorScheme).toBe("dark");
  });

  it("falls back to the system theme when set to system", async () => {
    localStorage.setItem("negosyohub-theme", "system");
    vi.stubGlobal("matchMedia", mockMatchMedia(true));

    const { useTheme } = await import("@/composables/useTheme");
    const theme = useTheme();

    expect(theme.theme.value).toBe("system");
    expect(theme.resolvedTheme.value).toBe("dark");
    expect(document.documentElement.classList.contains("dark")).toBe(true);
  });

  it("toggles from dark to light and persists the chosen theme", async () => {
    localStorage.setItem("negosyohub-theme", "dark");
    vi.stubGlobal("matchMedia", mockMatchMedia(false));

    const { useTheme } = await import("@/composables/useTheme");
    const theme = useTheme();

    theme.toggleTheme();
    await nextTick();

    expect(theme.theme.value).toBe("light");
    expect(theme.isDark.value).toBe(false);
    expect(localStorage.getItem("negosyohub-theme")).toBe("light");
    expect(document.documentElement.classList.contains("dark")).toBe(false);
    expect(document.documentElement.dataset.theme).toBe("light");
  });
});
