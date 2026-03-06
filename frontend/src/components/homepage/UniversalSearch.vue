<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

const router = useRouter();
const query = ref("");
const activeFilter = ref("all");

const filters = [
  { key: "all", label: "All" },
  { key: "ecommerce", label: "E-Commerce" },
  { key: "real_estate", label: "Real Estate" },
  { key: "services", label: "Services" },
];

function handleSearch() {
  if (!query.value.trim()) return;
  if (activeFilter.value === "real_estate") {
    router.push({ path: "/properties", query: { q: query.value } });
  } else {
    router.push({ path: "/stores", query: { q: query.value, sector: activeFilter.value === "all" ? undefined : activeFilter.value } });
  }
}
</script>

<template>
  <section class="relative -mt-8 z-10 px-4 sm:px-6 pb-4">
    <div class="mx-auto max-w-3xl">
      <form
        @submit.prevent="handleSearch"
        class="rounded-2xl bg-white p-3 sm:p-4 shadow-[0_8px_24px_rgba(15,32,68,0.12)] ring-1 ring-slate-100"
      >
        <!-- Search input row -->
        <div class="flex items-center gap-3">
          <MagnifyingGlassIcon class="size-5 shrink-0 text-slate-400" />
          <input
            v-model="query"
            type="text"
            placeholder="Search products, properties, or stores…"
            class="flex-1 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 outline-none sm:text-base"
          />
          <button
            type="submit"
            class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 hover:shadow-emerald-600/25 hover:shadow-md active:scale-[0.98] transition-all"
          >
            Search
          </button>
        </div>

        <!-- Filter pills -->
        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="filter in filters"
            :key="filter.key"
            type="button"
            @click="activeFilter = filter.key"
            class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all"
            :class="
              activeFilter === filter.key
                ? 'bg-navy-900 text-white shadow-sm'
                : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
            "
          >
            {{ filter.label }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>
