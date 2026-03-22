<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useAppI18n } from "@/i18n";

const auth = useAuthStore();
const { t } = useAppI18n();

const email = ref("");
const loading = ref(false);
const error = ref(null);
const success = ref(false);

async function submit() {
  loading.value = true;
  error.value = null;

  try {
    await auth.forgotPassword(email.value);
    success.value = true;
  } catch (err) {
    error.value =
      err?.response?.data?.message ??
      err?.response?.data?.errors?.email?.[0] ??
      t("auth.forgotPassword.error");
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
        {{ t("auth.forgotPassword.title") }}
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        {{ t("auth.forgotPassword.subtitle") }}
      </p>
    </div>

    <!-- Success state -->
    <template v-if="success">
      <div
        class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700"
      >
        <p class="font-medium">{{ t("auth.forgotPassword.checkInbox") }}</p>
        <p class="mt-1 text-green-600">
          {{ t("auth.forgotPassword.success", { email }) }}
        </p>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        <RouterLink
          to="/login"
          class="font-semibold text-brand-600 hover:text-brand-700 hover:underline"
        >
          {{ t("auth.forgotPassword.backToLogin") }}
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
      </div>

      <!-- Email -->
      <div class="space-y-1">
        <label
          for="fp-email"
          class="text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          {{ t("auth.fields.email") }}
        </label>
        <input
          id="fp-email"
          v-model="email"
          type="email"
          autocomplete="email"
          required
          :placeholder="t('auth.fields.emailPlaceholder')"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200"
        />
      </div>

      <!-- Submit -->
      <button
        type="submit"
        :disabled="loading"
        class="mt-5 w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-600 active:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
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
          {{ t("auth.forgotPassword.submitting") }}
        </span>
        <span v-else>{{ t("auth.forgotPassword.submit") }}</span>
      </button>

      <!-- Back to login -->
      <p class="mt-6 text-center text-sm text-slate-500">
        {{ t("auth.forgotPassword.remembered") }}
        <RouterLink
          to="/login"
          class="font-semibold text-brand-600 hover:text-brand-700 hover:underline"
        >
          {{ t("auth.login.submit") }}
      </RouterLink>
      </p>
    </form>
  </div>
</template>
