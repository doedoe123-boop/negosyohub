<script setup>
import { ref } from "vue";
import { CheckCircleIcon, XCircleIcon } from "@heroicons/vue/20/solid";
import { couponsApi } from "@/api/coupons";

const emit = defineEmits(["applied", "removed"]);

const code = ref("");
const loading = ref(false);
const applied = ref(null); // holds the validated coupon object
const errorMsg = ref("");

async function applyCoupon() {
  if (!code.value.trim()) return;

  loading.value = true;
  errorMsg.value = "";

  try {
    const { data } = await couponsApi.validate(code.value.trim());
    applied.value = data;
    emit("applied", data);
  } catch (e) {
    errorMsg.value =
      e.response?.data?.message ?? "Invalid or expired coupon code.";
    applied.value = null;
  } finally {
    loading.value = false;
  }
}

function removeCoupon() {
  applied.value = null;
  code.value = "";
  errorMsg.value = "";
  emit("removed");
}
</script>

<template>
  <div class="space-y-2">
    <label class="theme-text-muted block text-xs font-medium">
      Coupon Code
    </label>

    <!-- Applied state -->
    <div
      v-if="applied"
      class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5"
    >
      <div class="flex items-center gap-2 text-sm">
        <CheckCircleIcon class="size-4 text-emerald-500" />
        <span class="font-semibold text-emerald-700">{{ applied.code }}</span>
        <span class="text-emerald-600">— {{ applied.description }}</span>
      </div>
      <button
        type="button"
        class="text-xs font-medium text-emerald-600 hover:text-emerald-800 transition-colors"
        @click="removeCoupon"
      >
        Remove
      </button>
    </div>

    <!-- Input state -->
    <div v-else class="flex gap-2">
      <input
        v-model="code"
        type="text"
        placeholder="Enter coupon code"
        class="theme-input flex-1 rounded-xl px-3 py-2.5 text-sm"
        @keyup.enter="applyCoupon"
      />
      <button
        type="button"
        :disabled="loading || !code.trim()"
        class="btn-brand shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
        @click="applyCoupon"
      >
        {{ loading ? "Checking…" : "Apply" }}
      </button>
    </div>

    <!-- Error -->
    <p v-if="errorMsg" class="flex items-center gap-1 text-xs text-red-600">
      <XCircleIcon class="size-3.5" />
      {{ errorMsg }}
    </p>
  </div>
</template>
