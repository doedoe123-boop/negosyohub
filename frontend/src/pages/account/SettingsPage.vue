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
  <div class="mx-auto max-w-xl px-4 py-8 sm:px-0 space-y-8">
    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
      Settings
    </h1>

    <!-- Notifications -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="mb-4 text-base font-bold text-slate-900">Notifications</h2>

      <div class="space-y-4">
        <label class="flex cursor-pointer items-center justify-between gap-3">
          <div>
            <p class="text-sm font-medium text-slate-800">
              Order status updates
            </p>
            <p class="text-xs text-slate-400">
              Receive email when your order status changes.
            </p>
          </div>
          <input
            v-model="notifications.order_updates"
            type="checkbox"
            class="size-5 rounded border-slate-300 accent-brand-600"
          />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-3">
          <div>
            <p class="text-sm font-medium text-slate-800">Promotions & deals</p>
            <p class="text-xs text-slate-400">
              Occasional emails about new stores, discounts, and events.
            </p>
          </div>
          <input
            v-model="notifications.promotions"
            type="checkbox"
            class="size-5 rounded border-slate-300 accent-brand-600"
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
        class="mt-5 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-bold text-white transition-all hover:from-brand-600 hover:to-brand-700 disabled:opacity-60"
        :disabled="savingNotifications"
        @click="saveNotifications"
      >
        {{ savingNotifications ? "Saving…" : "Save Preferences" }}
      </button>
    </section>

    <!-- Security -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="mb-4 text-base font-bold text-slate-900">Security</h2>
      <RouterLink
        to="/account/password"
        class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
      >
        <KeyIcon class="size-5 text-slate-400" />
        Change Password
      </RouterLink>
    </section>

    <!-- Danger zone -->
    <section class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
      <h2 class="mb-1 text-base font-bold text-red-700">Danger Zone</h2>
      <p class="mb-4 text-sm text-slate-500">
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="showDeleteModal = false"
      >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
          <h3 class="mb-2 text-lg font-bold text-slate-900">
            Delete your account?
          </h3>
          <p class="mb-4 text-sm text-slate-500">
            This cannot be undone. Type your email
            <strong>{{ auth.user?.email }}</strong> to confirm.
          </p>

          <input
            v-model="deletePhrase"
            type="email"
            :placeholder="auth.user?.email"
            class="mb-4 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-red-400 focus:ring-2 focus:ring-red-100 focus:outline-none"
          />

          <div class="flex justify-end gap-3">
            <button
              class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
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
