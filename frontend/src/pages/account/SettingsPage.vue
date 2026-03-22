<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { authApi } from "@/api/auth";
import { KeyIcon } from "@heroicons/vue/24/outline";

const auth = useAuthStore();

const notifications = ref({
  order_updates: true,
  promotions: false,
});
const savingNotifications = ref(false);
const notifSuccess = ref(false);

const deletePhrase = ref("");
const deletingAccount = ref(false);
const showDeleteModal = ref(false);

onMounted(async () => {
  const prefs = auth.user?.notification_preferences;

  if (prefs) {
    notifications.value = { ...notifications.value, ...prefs };
  }
});

async function saveNotifications() {
  savingNotifications.value = true;
  notifSuccess.value = false;

  try {
    await authApi.updateSettings({
      notification_preferences: notifications.value,
    });
    notifSuccess.value = true;
    setTimeout(() => (notifSuccess.value = false), 3000);
  } finally {
    savingNotifications.value = false;
  }
}

async function deleteAccount() {
  if (deletePhrase.value !== auth.user?.email) {
    return;
  }

  deletingAccount.value = true;

  try {
    await authApi.deleteAccount();
    auth.user = null;
    sessionStorage.removeItem("api_token");
    window.location.href = "/";
  } catch {
    deletingAccount.value = false;
  }
}
</script>

<template>
  <div class="mx-auto max-w-xl space-y-8 px-4 py-8 sm:px-0">
    <h1 class="theme-title text-2xl font-extrabold tracking-tight">
      Settings
    </h1>

    <!-- Notifications -->
    <section class="theme-card rounded-2xl p-6">
      <h2 class="theme-title mb-4 text-base font-bold">Notifications</h2>

      <div class="space-y-4">
        <label class="flex cursor-pointer items-center justify-between gap-3">
          <div>
            <p class="theme-title text-sm font-medium">
              Order status updates
            </p>
            <p class="theme-copy text-xs">
              Receive email when your order status changes.
            </p>
          </div>
          <input
            v-model="notifications.order_updates"
            type="checkbox"
            class="size-5 rounded accent-brand-600"
            style="border-color: var(--color-border)"
          />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-3">
          <div>
            <p class="theme-title text-sm font-medium">Promotions & deals</p>
            <p class="theme-copy text-xs">
              Occasional emails about new stores, discounts, and events.
            </p>
          </div>
          <input
            v-model="notifications.promotions"
            type="checkbox"
            class="size-5 rounded accent-brand-600"
            style="border-color: var(--color-border)"
          />
        </label>
      </div>

      <p
        v-if="notifSuccess"
        class="mt-4 rounded-xl bg-green-50 px-4 py-2 text-sm font-medium text-green-700"
      >
        ✓ Preferences saved.
      </p>

      <button
        class="btn-primary mt-5 rounded-xl px-5 py-2 text-sm font-bold transition-all disabled:opacity-60"
        :disabled="savingNotifications"
        @click="saveNotifications"
      >
        {{ savingNotifications ? "Saving…" : "Save Preferences" }}
      </button>
    </section>

    <!-- Security -->
    <section class="theme-card rounded-2xl p-6">
      <h2 class="theme-title mb-4 text-base font-bold">Security</h2>
      <RouterLink
        to="/account/password"
        class="btn-secondary flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors"
      >
        <KeyIcon class="theme-copy size-5" />
        Change Password
      </RouterLink>
    </section>

    <!-- Danger zone -->
    <section class="theme-card rounded-2xl border-red-200 p-6 shadow-sm">
      <h2 class="mb-1 text-base font-bold text-red-700">Danger Zone</h2>
      <p class="theme-copy mb-4 text-sm">
        Once deleted, your account and all data are permanently removed.
      </p>
      <button
        class="rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
        @click="showDeleteModal = true"
      >
        Delete My Account
      </button>
    </section>

    <!-- Delete confirmation modal -->
    <Teleport to="body">
      <div
        v-if="showDeleteModal"
        class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showDeleteModal = false"
      >
        <div class="theme-modal w-full max-w-sm rounded-2xl p-6 shadow-xl">
          <h3 class="theme-title mb-2 text-lg font-bold">
            Delete your account?
          </h3>
          <p class="theme-copy mb-4 text-sm">
            This cannot be undone. Type your email
            <strong>{{ auth.user?.email }}</strong> to confirm.
          </p>

          <input
            v-model="deletePhrase"
            type="email"
            :placeholder="auth.user?.email"
            class="theme-input mb-4 w-full rounded-xl px-4 py-2.5 text-sm"
          />

          <div class="flex justify-end gap-3">
            <button
              class="btn-secondary rounded-xl px-4 py-2 text-sm font-medium transition-colors"
              @click="showDeleteModal = false"
            >
              Cancel
            </button>
            <button
              :disabled="deletePhrase !== auth.user?.email || deletingAccount"
              class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-50"
              @click="deleteAccount"
            >
              {{ deletingAccount ? "Deleting…" : "Permanently Delete" }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
