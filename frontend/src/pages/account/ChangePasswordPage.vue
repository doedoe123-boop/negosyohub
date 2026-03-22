<script setup>
import { ref } from "vue";
import { authApi } from "@/api/auth";

const form = ref({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const saving = ref(false);
const success = ref(false);
const errors = ref({});

async function submit() {
  saving.value = true;
  success.value = false;
  errors.value = {};

  try {
    await authApi.changePassword(form.value);
    success.value = true;
    form.value = {
      current_password: "",
      password: "",
      password_confirmation: "",
    };
    setTimeout(() => (success.value = false), 4000);
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {};
    } else {
      errors.value = {
        current_password: [
          err.response?.data?.message ?? "Something went wrong.",
        ],
      };
    }
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="mx-auto max-w-xl px-4 py-8 sm:px-0">
    <h1 class="theme-title mb-6 text-2xl font-extrabold tracking-tight">
      Change Password
    </h1>

    <div class="theme-card rounded-2xl p-6">
      <form class="space-y-5" @submit.prevent="submit">
        <!-- Current password -->
        <div>
          <label
            for="current-password"
            class="theme-title mb-1.5 block text-sm font-medium"
            >Current Password</label
          >
          <input
            id="current-password"
            v-model="form.current_password"
            type="password"
            required
            autocomplete="current-password"
            class="theme-input w-full rounded-xl px-4 py-2.5 text-sm"
            :class="{ 'border-red-300': errors.current_password }"
          />
          <p v-if="errors.current_password" class="mt-1 text-xs text-red-600">
            {{ errors.current_password[0] }}
          </p>
        </div>

        <!-- New password -->
        <div>
          <label
            for="new-password"
            class="theme-title mb-1.5 block text-sm font-medium"
            >New Password</label
          >
          <input
            id="new-password"
            v-model="form.password"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="theme-input w-full rounded-xl px-4 py-2.5 text-sm"
            :class="{ 'border-red-300': errors.password }"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-600">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- Confirm new password -->
        <div>
          <label
            for="confirm-password"
            class="theme-title mb-1.5 block text-sm font-medium"
            >Confirm New Password</label
          >
          <input
            id="confirm-password"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="theme-input w-full rounded-xl px-4 py-2.5 text-sm"
          />
        </div>

        <!-- Feedback -->
        <p
          v-if="success"
          class="rounded-xl bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700"
        >
          ✓ Password updated successfully.
        </p>

        <button
          type="submit"
          :disabled="saving"
          class="btn-primary w-full rounded-xl px-6 py-2.5 text-sm font-bold transition-all disabled:opacity-60"
        >
          {{ saving ? "Updating…" : "Update Password" }}
        </button>
      </form>
    </div>
  </div>
</template>
