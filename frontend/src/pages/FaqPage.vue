<script setup>
import { ref, onMounted } from "vue";
import { ChevronDownIcon } from "@heroicons/vue/20/solid";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { faqApi } from "@/api/faq";
import { sanitizeHtml } from "@/utils/sanitizeHtml";
import { useAppI18n } from "@/i18n";

const { t } = useAppI18n();

useSeoMeta(() => ({
  title: t("faq.title"),
  description: t("faq.introDescription"),
}));

const faqs = ref([]);
const loading = ref(true);
const error = ref(null);
const openId = ref(null);

onMounted(async () => {
  try {
    const { data } = await faqApi.list();
    faqs.value = data;
  } catch {
    error.value = t("faq.loadError");
  } finally {
    loading.value = false;
  }
});

function toggle(id) {
  openId.value = openId.value === id ? null : id;
}

function sanitizedAnswer(answer) {
  return sanitizeHtml(answer);
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <!-- Header -->
    <div class="mb-10 text-center">
      <h1 class="text-3xl font-bold tracking-tight text-slate-800 dark:text-white">
        {{ t("faq.title") }}
      </h1>
      <p class="mt-3 text-base text-slate-500 dark:text-gray-300">
        {{ t("faq.introPrefix") }}
        <RouterLink
          to="/account/help"
          class="font-medium text-brand-600 hover:underline"
        >
          {{ t("faq.helpCenter") }}
        </RouterLink>
        {{ t("faq.introMiddle") }}
        <RouterLink
          to="/contact"
          class="font-medium text-brand-600 hover:underline"
        >
          {{ t("faq.contactUs") }}</RouterLink
        >.
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 6"
        :key="i"
        class="h-14 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-700"
      />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300"
    >
      {{ error }}
    </div>

    <!-- Empty -->
    <div v-else-if="faqs.length === 0" class="py-10 text-center text-slate-400">
      {{ t("faq.empty") }}
    </div>

    <!-- Accordion -->
    <div
      v-else
      class="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white shadow-sm dark:bg-slate-900 dark:border-slate-700 dark:divide-slate-700"
    >
      <div v-for="faq in faqs" :key="faq.id">
        <button
          class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left hover:bg-slate-50 dark:hover:bg-slate-800"
          :aria-expanded="openId === faq.id"
          @click="toggle(faq.id)"
        >
          <span class="text-sm font-semibold text-slate-800 dark:text-white">{{
            faq.question
          }}</span>
          <ChevronDownIcon
            class="size-5 shrink-0 text-slate-400 transition-transform duration-200"
            :class="{ 'rotate-180': openId === faq.id }"
          />
        </button>
        <div
          v-show="openId === faq.id"
          class="border-t border-slate-100 px-6 py-5 text-sm leading-relaxed text-slate-600 dark:border-slate-700 dark:text-slate-300"
          v-html="sanitizedAnswer(faq.answer)"
        />
      </div>
    </div>
  </div>
</template>
