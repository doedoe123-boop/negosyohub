<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const form = ref({ email: "", password: "" });
const error = ref(null);
const loading = ref(false);

async function submit() {
  loading.value = true;
  error.value = null;
  try {
    await auth.login(form.value);
    const redirect = route.query.redirect ?? "/";
    router.push(redirect);
  } catch (e) {
    error.value = e.response?.data?.message ?? "Invalid credentials.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-center text-2xl font-bold text-gray-900">Sign In</h1>

    <p
      v-if="error"
      class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"
    >
      {{ error }}
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label
          for="login-email"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Email</label
        >
        <input
          id="login-email"
          v-model="form.email"
          type="email"
          required
          class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
      </div>
      <div>
        <label
          for="login-password"
          class="mb-1 block text-sm font-medium text-gray-700"
          >Password</label
        >
        <input
          id="login-password"
          v-model="form.password"
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
        {{ loading ? "Signing in…" : "Sign In" }}
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-500">
      No account?
      <a href="/register" class="font-medium text-brand-600 hover:underline"
        >Register</a
      >
    </p>
  </div>
</template>
