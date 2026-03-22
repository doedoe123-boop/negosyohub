import { useTheme } from "@/composables/useTheme";

export function useDarkMode() {
  const { isDark, toggleTheme } = useTheme();

  return {
    isDark,
    toggleDark: toggleTheme,
  };
}
