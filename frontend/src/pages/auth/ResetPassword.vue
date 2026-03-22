<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useAppI18n } from "@/i18n";

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const { t } = useAppI18n();

const token = computed(() => route.query.token ?? "");
const emailFromQuery = computed(() => route.query.email ?? "");

const form = ref({
  password: "",
  password_confirmation: "",
});
const loading = ref(false);
const error = ref(null);
const showPassword = ref(false);
const showConfirm = ref(false);

const tokenMissing = computed(() => !token.value || !emailFromQuery.value);

async function submit() {
  loading.value = true;
  error.value = null;

  try {
    await auth.resetPassword({
      token: token.value,
      email: emailFromQuery.value,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    });

    router.push({ name: "auth.login", query: { passwordReset: "1" } });
  } catch (err) {
    const errors = err?.response?.data?.errors ?? {};
    const first = Object.values(errors).flat()[0];
    error.value =
      first ??
      err?.response?.data?.message ??
      t("auth.resetPassword.error");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="w-full max-w-sm">
    <!-- Header -->
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight text-slate-800">
        {{ t("auth.resetPassword.title") }}
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        {{ t("auth.resetPassword.subtitle") }}
      </p>
    </div>

    <!-- Invalid / expired link -->
    <template v-if="tokenMissing">
      <div
        class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
      >
        <p class="font-medium">{{ t("auth.resetPassword.invalidTitle") }}</p>
        <p class="mt-1 text-red-600">
          {{ t("auth.resetPassword.invalidBody") }}
        </p>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        <RouterLink
          to="/forgot-password"
          class="font-semibold text-brand-600 hover:text-brand-700 hover:underline"
        >
          {{ t("auth.resetPassword.requestNew") }}
        </RouterLink>
      </p>
    </template>

    <!-- Form -->
    <form v-else novalidate @submit.prevent="submit">
      <!-- Error alert -->
      <div
        v-if="error"
        class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"
      >
        {{ error }}
        <RouterLink
          to="/forgot-password"
          class="ml-1 font-semibold underline hover:text-red-800"
        >
          {{ t("auth.resetPassword.requestNew") }}
        </RouterLink>
      </div>

      <div class="space-y-4">
        <!-- New password -->
        <div class="space-y-1">
          <label
            for="rp-password"
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            {{ t("auth.fields.newPassword") }}
          </label>
          <div class="relative">
            <input
              id="rp-password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              required
              :placeholder="t('auth.fields.newPasswordPlaceholder')"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-800 placeholder-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600"
              @click="showPassword = !showPassword"
            >
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

        <!-- Confirm password -->
        <div class="space-y-1">
          <label
            for="rp-confirm"
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            {{ t("auth.fields.confirmNewPassword") }}
          </label>
          <div class="relative">
            <input
              id="rp-confirm"
              v-model="form.password_confirmation"
              :type="showConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              required
              :placeholder="t('auth.fields.confirmNewPasswordPlaceholder')"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-800 placeholder-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600"
              @click="showConfirm = !showConfirm"
            >
              <svg
                v-if="showConfirm"
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
      </div>

      <!-- Submit -->
      <button
        type="submit"
        :disabled="loading"
        class="mt-6 w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-600 active:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
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
          {{ t("auth.resetPassword.submitting") }}
        </span>
        <span v-else>{{ t("auth.resetPassword.submit") }}</span>
      </button>
    </form>
  </div>
</template>
