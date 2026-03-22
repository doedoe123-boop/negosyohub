<script setup>
import { ref } from "vue";
import { useRouter, useRoute, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useAppI18n } from "@/i18n";

const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const { t } = useAppI18n();

const form = ref({ email: "", password: "" });
const error = ref(null);
const loading = ref(false);
const showPassword = ref(false);

async function submit() {
  loading.value = true;
  error.value = null;
  try {
    await auth.login(form.value);
    const redirect = route.query.redirect ?? "/";
    router.push(redirect);
  } catch (e) {
    error.value = e.response?.data?.message ?? t("auth.login.invalid");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
    <!-- Heading -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-900">{{ t("auth.login.title") }}</h1>
      <p class="mt-1 text-sm text-slate-500">
        {{ t("auth.login.subtitle") }}
      </p>
    </div>

    <!-- Error -->
    <p
      v-if="error"
      class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600"
    >
      {{ error }}
    </p>

    <form class="space-y-5" @submit.prevent="submit">
      <!-- Email -->
      <div>
        <label
          for="login-email"
          class="mb-1.5 block text-sm font-medium text-slate-700"
        >
          {{ t("auth.fields.email") }}
        </label>
        <input
          id="login-email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          :placeholder="t('auth.fields.emailPlaceholder')"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
        />
      </div>

      <!-- Password -->
      <div>
        <div class="mb-1.5 flex items-center justify-between">
          <label
            for="login-password"
            class="text-sm font-medium text-slate-700"
          >
            {{ t("auth.fields.password") }}
          </label>
          <RouterLink
            to="/forgot-password"
            class="text-xs font-medium text-brand-600 hover:underline"
          >
            {{ t("auth.login.forgotPassword") }}
          </RouterLink>
        </div>
        <div class="relative">
          <input
            id="login-password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            required
            :placeholder="t('auth.fields.passwordPlaceholder')"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @click="showPassword = !showPassword"
          >
            <!-- Eye / EyeOff inline SVGs to avoid extra import -->
            <svg
              v-if="showPassword"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.75"
              stroke="currentColor"
              class="size-4.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.75"
              stroke="currentColor"
              class="size-4.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Submit -->
      <button
        type="submit"
        :disabled="loading"
        class="mt-1 w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-600 active:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <span v-if="loading" class="flex items-center justify-center gap-2">
          <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
          {{ t("auth.login.submitting") }}
        </span>
        <span v-else>{{ t("auth.login.submit") }}</span>
      </button>
    </form>

    <!-- Register link -->
    <p class="mt-6 text-center text-sm text-slate-500">
      {{ t("auth.login.noAccount") }}
      <RouterLink
        to="/register"
        class="font-semibold text-brand-600 hover:text-brand-700 hover:underline"
      >
        {{ t("nav.register") }}
      </RouterLink>
    </p>

    <!-- Seller portal divider -->
    <div class="mt-8 border-t border-slate-100 pt-6 text-center">
      <p class="text-xs text-slate-400">
        Seller or listing agent?
        <a
          :href="`${backendUrl}/register/sector`"
          target="_blank"
          class="font-medium text-slate-500 underline transition-colors hover:text-brand-600"
        >
          {{ t("auth.login.sellerPortal") }}
        </a>
      </p>
    </div>
  </div>
</template>
