<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();
const active = ref(route.query.category ?? "");

const categories = [
  { label: "All",          value: "",              icon: "🏪" },
  { label: "Electronics",  value: "electronics",   icon: "📱" },
  { label: "Fashion",      value: "fashion",       icon: "👗" },
  { label: "Home & Living",value: "home",          icon: "🛋️" },
  { label: "Food",         value: "food",          icon: "🛒" },
  { label: "Gadgets",      value: "gadgets",       icon: "💻" },
  { label: "Beauty",       value: "beauty",        icon: "💄" },
  { label: "Sports",       value: "sports",        icon: "⚽" },
  { label: "Properties",   value: "properties",    icon: "🏡" },
];

function select(cat) {
  active.value = cat.value;
  if (cat.value === "properties") {
    router.push("/properties");
  } else {
    router.push({ path: "/stores", query: cat.value ? { category: cat.value } : {} });
  }
}
</script>

<template>
  <div class="border-b border-slate-100 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <div class="flex items-center gap-2 overflow-x-auto py-2.5 scrollbar-none">
        <button
          v-for="cat in categories"
          :key="cat.value"
          type="button"
          class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold whitespace-nowrap transition-all"
          :class="
            active === cat.value
              ? 'bg-navy-900 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-600 hover:border-emerald-300 hover:text-emerald-700'
          "
          @click="select(cat)"
        >
          <span class="text-sm leading-none">{{ cat.icon }}</span>
          {{ cat.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
</style>
