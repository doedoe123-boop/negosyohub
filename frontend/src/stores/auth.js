import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authApi } from "@/api/auth";
import { useAppI18n } from "@/i18n";
import { useCartStore } from "@/stores/cart";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const initialized = ref(false);
  const { setLocale } = useAppI18n();

  const isLoggedIn = computed(() => !!user.value);
  const isCustomer = computed(() => user.value?.role === "customer");

  async function fetchUser() {
    try {
      const { data } = await authApi.me();
      user.value = data;
      if (data?.preferred_locale) {
        await setLocale(data.preferred_locale);
      }
    } catch {
      user.value = null;
    } finally {
      initialized.value = true;
    }
  }

  async function register(payload) {
    const { data } = await authApi.register(payload);
    if (data.token) {
      sessionStorage.setItem("api_token", data.token);
    }
    user.value = data.user;
    if (data.user?.preferred_locale) {
      await setLocale(data.user.preferred_locale);
    }
    return data;
  }

  async function login(credentials) {
    const { data } = await authApi.login(credentials);
    if (data.token) {
      sessionStorage.setItem("api_token", data.token);
    }
    user.value = data.user;
    if (data.user?.preferred_locale) {
      await setLocale(data.user.preferred_locale);
    }
    return data;
  }

  async function logout() {
    await authApi.logout();
    sessionStorage.removeItem("api_token");
    user.value = null;
    useCartStore().reset();
  }

  async function forgotPassword(email) {
    const { data } = await authApi.forgotPassword({ email });
    return data;
  }

  async function resetPassword(payload) {
    const { data } = await authApi.resetPassword(payload);
    if (data.token) {
      sessionStorage.setItem("api_token", data.token);
    }
    if (data.user) {
      user.value = data.user;
      if (data.user.preferred_locale) {
        await setLocale(data.user.preferred_locale);
      }
    }
    return data;
  }

  async function persistPreferredLocale(preferredLocale) {
    if (!user.value) {
      return;
    }

    const { data } = await authApi.updateSettings({
      preferred_locale: preferredLocale,
    });

    user.value = data;
  }

  return {
    user,
    initialized,
    isLoggedIn,
    isCustomer,
    fetchUser,
    register,
    login,
    logout,
    forgotPassword,
    resetPassword,
    persistPreferredLocale,
  };
});
