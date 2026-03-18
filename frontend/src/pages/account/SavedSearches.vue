<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  BookmarkIcon,
  TrashIcon,
  BellIcon,
  BellSlashIcon,
} from "@heroicons/vue/24/outline";
import { savedSearchesApi } from "@/api/savedSearches";

const searches = ref([]);
const loading = ref(true);
const error = ref(false);

onMounted(async () => {
  try {
    const { data } = await savedSearchesApi.list();
    searches.value = data.data ?? data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

async function removeSearch(id) {
  await savedSearchesApi.delete(id);
  searches.value = searches.value.filter((s) => s.id !== id);
}

async function toggleSearch(search) {
  await savedSearchesApi.toggle(search.id);
  search.is_active = !search.is_active;
}

function criteriaLabel(criteria) {
  const parts = [];
  if (criteria.property_type)
    parts.push(criteria.property_type.replace(/_/g, " "));
  if (criteria.listing_type)
    parts.push(criteria.listing_type.replace(/_/g, " "));
  if (criteria.city) parts.push(criteria.city);
  if (criteria.province) parts.push(criteria.province);
  if (criteria.bedrooms) parts.push(`${criteria.bedrooms}+ BR`);
  if (criteria.min_price || criteria.max_price) {
    const min = criteria.min_price
      ? `₱${Number(criteria.min_price).toLocaleString()}`
      : "";
    const max = criteria.max_price
      ? `₱${Number(criteria.max_price).toLocaleString()}`
      : "";
    if (min && max) parts.push(`${min} – ${max}`);
    else if (min) parts.push(`from ${min}`);
    else parts.push(`up to ${max}`);
  }
  return parts.length ? parts.join(" · ") : "All properties";
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
          Saved Searches
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Get notified when new matching properties are listed.
        </p>
      </div>
      <RouterLink
        to="/properties"
        class="shrink-0 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-emerald-700"
      >
        Browse Properties
      </RouterLink>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 3"
        :key="i"
        class="h-20 animate-pulse rounded-2xl bg-slate-100"
      />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="rounded-2xl border border-red-100 bg-red-50 py-8 text-center text-sm text-red-600"
    >
      Failed to load saved searches. Please refresh the page.
    </div>

    <!-- Empty -->
    <div
      v-else-if="searches.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white py-12 text-center"
    >
      <BookmarkIcon class="mx-auto mb-3 size-10 text-slate-300" />
      <p class="font-medium text-slate-500">No saved searches yet</p>
      <p class="mt-1 text-sm text-slate-400">
        Browse properties and use the "Save Search" button to save your filters.
      </p>
      <RouterLink
        to="/properties"
        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-emerald-700"
      >
        Browse Properties
      </RouterLink>
    </div>

    <!-- List -->
    <ul v-else class="space-y-3">
      <li
        v-for="search in searches"
        :key="search.id"
        class="flex items-start justify-between gap-4 rounded-2xl border bg-white p-4 shadow-sm transition-opacity"
        :class="{ 'opacity-60': !search.is_active }"
      >
        <div class="flex min-w-0 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-slate-900">{{ search.name }}</span>
            <span
              v-if="search.notify_frequency !== 'never'"
              class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-600"
            >
              {{ search.notify_frequency }}
            </span>
            <span
              v-if="!search.is_active"
              class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500"
            >
              Paused
            </span>
          </div>
          <p class="truncate text-xs text-slate-500">
            {{ criteriaLabel(search.criteria) }}
          </p>
          <p v-if="search.last_notified_at" class="text-[11px] text-slate-400">
            Last notified
            {{ new Date(search.last_notified_at).toLocaleDateString("en-PH") }}
          </p>
        </div>

        <div class="flex shrink-0 items-center gap-1">
          <button
            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600"
            :title="
              search.is_active ? 'Pause notifications' : 'Resume notifications'
            "
            @click="toggleSearch(search)"
          >
            <BellSlashIcon v-if="search.is_active" class="size-4" />
            <BellIcon v-else class="size-4" />
          </button>

          <RouterLink
            :to="`/properties?${new URLSearchParams(Object.fromEntries(Object.entries(search.criteria).filter(([, v]) => v))).toString()}`"
            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-emerald-600"
            title="Run this search"
          >
            <svg
              class="size-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </RouterLink>

          <button
            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-500"
            title="Delete saved search"
            @click="removeSearch(search.id)"
          >
            <TrashIcon class="size-4" />
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>
