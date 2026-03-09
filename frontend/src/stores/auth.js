import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authApi } from "@/api/auth";
import { useCartStore } from "@/stores/cart";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const initialized = ref(false);

  const isLoggedIn = computed(() => !!user.value);
  const isCustomer = computed(() => user.value?.role === "customer");

  async function fetchUser() {
    try {
      const { data } = await authApi.me();
      user.value = data;
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
    return data;
  }

  async function login(credentials) {
    const { data } = await authApi.login(credentials);
    if (data.token) {
      sessionStorage.setItem("api_token", data.token);
    }
    user.value = data.user;
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
    }
    return data;
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
  };
});
