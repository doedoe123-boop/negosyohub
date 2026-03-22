<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { authApi } from "@/api/auth";

const auth = useAuthStore();

const form = ref({
  name: auth.user?.name ?? "",
  phone: auth.user?.phone ?? "",
});

const saving = ref(false);
const success = ref(false);
const error = ref("");

async function save() {
  saving.value = true;
  success.value = false;
  error.value = "";

  try {
    const { data } = await authApi.updateProfile(form.value);
    auth.user = data;
    success.value = true;
    setTimeout(() => (success.value = false), 3000);
  } catch (err) {
    error.value =
      err.response?.data?.message ?? "Failed to update profile. Try again.";
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="mx-auto max-w-xl px-4 py-8 sm:px-0">
    <h1 class="theme-title mb-6 text-2xl font-extrabold tracking-tight">
      My Profile
    </h1>

    <div class="theme-card rounded-2xl p-6">
      <form class="space-y-5" @submit.prevent="save">
        <!-- Name -->
        <div>
          <label
            for="profile-name"
            class="theme-title mb-1.5 block text-sm font-medium"
            >Full Name</label
          >
          <input
            id="profile-name"
            v-model="form.name"
            type="text"
            required
            class="theme-input w-full rounded-xl px-4 py-2.5 text-sm"
          />
        </div>

        <!-- Email (read-only) -->
        <div>
          <label
            for="profile-email"
            class="theme-title mb-1.5 block text-sm font-medium"
            >Email Address</label
          >
          <input
            id="profile-email"
            :value="auth.user?.email"
            type="email"
            disabled
            class="theme-card-muted theme-copy w-full cursor-not-allowed rounded-xl px-4 py-2.5 text-sm"
          />
          <p class="theme-copy mt-1 text-xs">
            Email cannot be changed here.
          </p>
        </div>

        <!-- Phone -->
        <div>
          <label
            for="profile-phone"
            class="theme-title mb-1.5 block text-sm font-medium"
            >Phone Number</label
          >
          <input
            id="profile-phone"
            v-model="form.phone"
            type="tel"
            placeholder="e.g. 09171234567"
            class="theme-input w-full rounded-xl px-4 py-2.5 text-sm"
          />
        </div>

        <!-- Success / error feedback -->
        <p
          v-if="success"
          class="rounded-xl bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700"
        >
          ✓ Profile updated successfully.
        </p>
        <p
          v-if="error"
          class="rounded-xl bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700"
        >
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="saving"
          class="btn-primary w-full rounded-xl px-6 py-2.5 text-sm font-bold transition-all disabled:opacity-60"
        >
          {{ saving ? "Saving…" : "Save Changes" }}
        </button>
      </form>
    </div>
  </div>
</template>
