<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  HomeModernIcon,
  ChevronRightIcon,
  HeartIcon,
} from "@heroicons/vue/24/outline";
import { inquiriesApi } from "@/api/inquiries";

const inquiries = ref([]);
const loading = ref(true);
const error = ref(false);

const statusColors = {
  new: "bg-blue-100 text-blue-700",
  contacted: "bg-yellow-100 text-yellow-700",
  viewing_scheduled: "bg-purple-100 text-purple-700",
  negotiating: "bg-emerald-100 text-emerald-700",
  closed: "theme-badge-neutral",
};

onMounted(async () => {
  try {
    const { data } = await inquiriesApi.list();
    inquiries.value = data.data ?? data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="mb-6">
      <h1 class="theme-title text-2xl font-extrabold tracking-tight">
        My Inquiries
      </h1>
      <p class="theme-copy mt-1 text-sm">
        Properties you've expressed interest in.
      </p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="theme-skeleton h-20 animate-pulse rounded-2xl" />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="rounded-2xl border border-red-100 bg-red-50 py-8 text-center text-sm text-red-600"
    >
      Failed to load inquiries. Please refresh the page.
    </div>

    <!-- Empty -->
    <div
      v-else-if="inquiries.length === 0"
      class="theme-empty-state rounded-2xl py-12 text-center"
    >
      <HeartIcon class="theme-copy mx-auto mb-3 size-10" />
      <p class="theme-copy font-medium">No inquiries yet</p>
      <p class="theme-copy mt-1 text-sm">
        Browse properties and click "I'm Interested" to get started.
      </p>
      <RouterLink
        to="/properties"
        class="btn-primary mt-4 inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold transition-all"
      >
        Browse Properties
      </RouterLink>
    </div>

    <!-- Inquiries list -->
    <ul v-else class="space-y-3">
      <li v-for="inquiry in inquiries" :key="inquiry.id">
        <RouterLink
          :to="`/properties/${inquiry.property?.slug}`"
          class="theme-card theme-card-hover group flex items-center justify-between gap-3 rounded-2xl p-4"
        >
          <div class="flex min-w-0 items-center gap-3">
            <img
              v-if="inquiry.property?.featured_image"
              :src="inquiry.property.featured_image"
              :alt="inquiry.property.title"
              class="size-14 shrink-0 rounded-xl object-cover"
            />
            <div
              v-else
              class="theme-icon-muted flex size-14 shrink-0 items-center justify-center rounded-xl"
            >
              <HomeModernIcon class="theme-copy size-6" />
            </div>
            <div class="min-w-0">
              <p class="theme-title truncate font-semibold">
                {{ inquiry.property?.title }}
              </p>
              <p class="theme-copy text-xs">
                {{ inquiry.store?.name }}
                <span v-if="inquiry.property?.city">
                  · {{ inquiry.property.city }}
                </span>
              </p>
              <p class="theme-copy mt-0.5 text-xs">
                {{ inquiry.property?.formatted_price }}
              </p>
            </div>
          </div>
          <div class="flex shrink-0 flex-col items-end gap-1.5">
            <span
              class="rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="
                statusColors[inquiry.status] ?? 'theme-badge-neutral'
              "
            >
              {{ inquiry.status_label }}
            </span>
            <span
              v-if="inquiry.viewing_date"
              class="text-[11px] font-medium text-purple-600"
            >
              Viewing: {{ inquiry.viewing_date }}
            </span>
            <ChevronRightIcon
              class="theme-copy size-4 transition-colors group-hover:text-brand-500"
            />
          </div>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>
