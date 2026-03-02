<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const auth = useAuthStore();

const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const error = ref(null);
const loading = ref(false);

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
      : (e.response?.data?.message ?? "Registration failed.");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-center text-2xl font-bold text-gray-900">
      Create Account
    </h1>

    <p
      v-if="error"
      class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"
    >
      {{ error }}
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label
          for="register-name"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Full Name</label
        >
        <input
          id="register-name"
          v-model="form.name"
          type="text"
          required
          class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <div>
        <label
          for="register-email"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Email</label
        >
        <input
          id="register-email"
          v-model="form.email"
          type="email"
          required
          class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <div>
        <label
          for="register-password"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Password</label
        >
        <input
          id="register-password"
          v-model="form.password"
          type="password"
          required
          class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <div>
        <label
          for="register-password-confirm"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Confirm Password</label
        >
        <input
          id="register-password-confirm"
          v-model="form.password_confirmation"
          type="password"
          required
          class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <button
        type="submit"
        :disabled="loading"
        class="w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors disabled:opacity-50"
      >
        {{ loading ? "Creating account…" : "Create Account" }}
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-500">
      Already have an account?
      <a href="/login" class="font-medium text-brand-600 hover:underline"
        >Sign in</a
      >
    </p>
  </div>
</template>
