import { computed, ref, watch } from "vue";

export const THEME_STORAGE_KEY = "negosyohub-theme";

const VALID_THEMES = new Set(["light", "dark", "system"]);
const SYSTEM_THEME_QUERY = "(prefers-color-scheme: dark)";

function getSystemTheme() {
  if (typeof window === "undefined" || !window.matchMedia) {
    return "light";
  }

  return window.matchMedia(SYSTEM_THEME_QUERY).matches ? "dark" : "light";
}

function getStoredTheme() {
  if (typeof window === "undefined") {
    return "system";
  }

  const storedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);

  return VALID_THEMES.has(storedTheme) ? storedTheme : "system";
}

const theme = ref(getStoredTheme());
const systemTheme = ref(getSystemTheme());
const resolvedTheme = computed(() => {
  return theme.value === "system" ? systemTheme.value : theme.value;
});
const isDark = computed(() => resolvedTheme.value === "dark");

function applyTheme(nextTheme) {
  if (typeof document === "undefined") {
    return;
  }

  const root = document.documentElement;
  const isDarkTheme = nextTheme === "dark";

  root.classList.toggle("dark", isDarkTheme);
  root.dataset.theme = nextTheme;
  root.style.colorScheme = nextTheme;
}

function persistTheme(nextTheme) {
  if (typeof window === "undefined") {
    return;
  }

  window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
}

function syncTheme() {
  applyTheme(resolvedTheme.value);
  persistTheme(theme.value);
}

if (typeof window !== "undefined") {
  syncTheme();

  if (window.matchMedia) {
    const mediaQuery = window.matchMedia(SYSTEM_THEME_QUERY);
    const handleSystemThemeChange = (event) => {
      systemTheme.value = event.matches ? "dark" : "light";
    };

    if (typeof mediaQuery.addEventListener === "function") {
      mediaQuery.addEventListener("change", handleSystemThemeChange);
    } else if (typeof mediaQuery.addListener === "function") {
      mediaQuery.addListener(handleSystemThemeChange);
    }
  }
}

watch([theme, systemTheme], syncTheme, { immediate: false });

export function useTheme() {
  function setTheme(nextTheme) {
    theme.value = VALID_THEMES.has(nextTheme) ? nextTheme : "system";
  }

  function toggleTheme() {
    setTheme(isDark.value ? "light" : "dark");
  }

  return {
    theme,
    resolvedTheme,
    isDark,
    setTheme,
    toggleTheme,
  };
}
