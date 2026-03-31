<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const emit = defineEmits(["verified", "expired", "error"]);

const container = ref(null);
const widgetId = ref(null);

const siteKey = import.meta.env.VITE_TURNSTILE_SITEKEY;
const turnstileEnabled = import.meta.env.VITE_TURNSTILE_ENABLED === "true";

const renderWidget = async () => {
  if (!turnstileEnabled || !siteKey || !window.turnstile || widgetId.value !== null) {
    return;
  }

  await nextTick();

  if (!container.value) {
    return;
  }

  widgetId.value = window.turnstile.render(container.value, {
    sitekey: siteKey,
    callback: (token) => emit("verified", token),
    "expired-callback": () => emit("expired"),
    "error-callback": () => emit("error"),
  });
};

const reset = () => {
  if (window.turnstile && widgetId.value !== null) {
    window.turnstile.reset(widgetId.value);
  }
};

defineExpose({ reset });

onMounted(() => {
  renderWidget();
});

onBeforeUnmount(() => {
  if (window.turnstile && widgetId.value !== null && typeof window.turnstile.remove === "function") {
    window.turnstile.remove(widgetId.value);
  }
});
</script>

<template>
  <div v-if="turnstileEnabled && siteKey" ref="container" class="overflow-hidden rounded-xl" />
</template>
