<script setup>
import { ref, computed } from "vue";
import { useRouter, RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useAppI18n } from "@/i18n";

const router = useRouter();
const auth = useAuthStore();
const { t } = useAppI18n();

const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const error = ref(null);
const loading = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);

const passwordStrength = computed(() => {
  const p = form.value.password;
  if (!p) {
    return 0;
  }
  let score = 0;
  if (p.length >= 8) {
    score++;
  }
  if (/[A-Z]/.test(p)) {
    score++;
  }
  if (/[0-9]/.test(p)) {
    score++;
  }
  if (/[^A-Za-z0-9]/.test(p)) {
    score++;
  }
  return score;
});

const strengthLabel = computed(() => {
  const labels = ["", "Weak", "Fair", "Good", "Strong"];
  return labels[passwordStrength.value] ?? "";
});

const strengthColor = computed(() => {
  const colors = [
    "",
    "bg-red-400",
    "bg-yellow-400",
    "bg-blue-400",
    "bg-green-500",
  ];
  return colors[passwordStrength.value] ?? "";
});

async function submit() {
  loading.value = true;
  error.value = null;
  try {
    await auth.register(form.value);
    router.push("/");
  } catch (e) {
    const errors = e.response?.data?.errors;
    error.value = errors
      ? Object.values(errors).flat().join(" ")
      : (e.response?.data?.message ?? t("auth.register.failed"));
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
    <!-- Heading -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-900">{{ t("auth.register.title") }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t("auth.register.subtitle") }}</p>
    </div>

    <!-- Error -->
    <p
      v-if="error"
      class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600"
    >
      {{ error }}
    </p>

    <form class="space-y-5" @submit.prevent="submit">
      <!-- Full Name -->
      <div>
        <label
          for="register-name"
          class="mb-1.5 block text-sm font-medium text-slate-700"
        >
          {{ t("auth.fields.name") }}
        </label>
        <input
          id="register-name"
          v-model="form.name"
          type="text"
          autocomplete="name"
          required
          :placeholder="t('auth.fields.namePlaceholder')"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
        />
      </div>

      <!-- Email -->
      <div>
        <label
          for="register-email"
          class="mb-1.5 block text-sm font-medium text-slate-700"
        >
          {{ t("auth.fields.email") }}
        </label>
        <input
          id="register-email"
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
        <label
          for="register-password"
          class="mb-1.5 block text-sm font-medium text-slate-700"
        >
          {{ t("auth.fields.password") }}
        </label>
        <div class="relative">
          <input
            id="register-password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            required
            :placeholder="t('auth.fields.passwordPlaceholder')"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600"
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
        <!-- Password strength meter -->
        <div v-if="form.password" class="mt-2">
          <div class="mb-1 flex gap-1">
            <div
              v-for="i in 4"
              :key="i"
              class="h-1 flex-1 rounded-full transition-colors duration-300"
              :class="i <= passwordStrength ? strengthColor : 'bg-slate-200'"
            />
          </div>
          <span class="text-xs text-slate-400">{{ strengthLabel }}</span>
        </div>
      </div>

      <!-- Confirm Password -->
      <div>
        <label
          for="register-password-confirm"
          class="mb-1.5 block text-sm font-medium text-slate-700"
        >
          {{ t("auth.fields.confirmPassword") }}
        </label>
        <div class="relative">
          <input
            id="register-password-confirm"
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            autocomplete="new-password"
            required
            :placeholder="t('auth.fields.confirmPasswordPlaceholder')"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600"
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
          {{ t("auth.register.submitting") }}
        </span>
        <span v-else>{{ t("auth.register.submit") }}</span>
      </button>
    </form>

    <!-- Login link -->
    <p class="mt-6 text-center text-sm text-slate-500">
      {{ t("auth.register.haveAccount") }}
      <RouterLink
        to="/login"
        class="font-semibold text-brand-600 hover:text-brand-700 hover:underline"
      >
        {{ t("auth.register.signIn") }}
      </RouterLink>
    </p>

    <!-- Seller portal divider -->
    <div class="mt-8 border-t border-slate-100 pt-6 text-center">
      <p class="text-xs text-slate-400">
        {{ t("auth.register.sellerPrompt") }}
        <a
          href="http://localhost:8080/register/sector"
          class="font-medium text-slate-500 underline transition-colors hover:text-brand-600"
        >
          {{ t("auth.register.sellerCta") }}
        </a>
      </p>
    </div>
  </div>
</template>
