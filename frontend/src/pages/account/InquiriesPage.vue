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
  closed: "bg-slate-100 text-slate-600",
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
      <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
        My Inquiries
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Properties you've expressed interest in.
      </p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 4"
        :key="i"
        class="h-20 animate-pulse rounded-2xl bg-slate-100"
      />
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
      class="rounded-2xl border border-dashed border-slate-200 bg-white py-12 text-center"
    >
      <HeartIcon class="mx-auto mb-3 size-10 text-slate-300" />
      <p class="font-medium text-slate-500">No inquiries yet</p>
      <p class="mt-1 text-sm text-slate-400">
        Browse properties and click "I'm Interested" to get started.
      </p>
      <RouterLink
        to="/properties"
        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-all hover:from-brand-600 hover:to-brand-700"
      >
        Browse Properties
      </RouterLink>
    </div>

    <!-- Inquiries list -->
    <ul v-else class="space-y-3">
      <li v-for="inquiry in inquiries" :key="inquiry.id">
        <RouterLink
          :to="`/properties/${inquiry.property?.slug}`"
          class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-brand-200 hover:shadow-md"
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
              class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-slate-100"
            >
              <HomeModernIcon class="size-6 text-slate-400" />
            </div>
            <div class="min-w-0">
              <p class="truncate font-semibold text-slate-800">
                {{ inquiry.property?.title }}
              </p>
              <p class="text-xs text-slate-500">
                {{ inquiry.store?.name }}
                <span v-if="inquiry.property?.city">
                  · {{ inquiry.property.city }}
                </span>
              </p>
              <p class="mt-0.5 text-xs text-slate-400">
                {{ inquiry.property?.formatted_price }}
              </p>
            </div>
          </div>
          <div class="flex shrink-0 flex-col items-end gap-1.5">
            <span
              class="rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="
                statusColors[inquiry.status] ?? 'bg-slate-100 text-slate-500'
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
              class="size-4 text-slate-300 transition-colors group-hover:text-brand-500"
            />
          </div>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>
