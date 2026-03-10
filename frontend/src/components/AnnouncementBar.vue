<script setup>
import { ref, onMounted } from "vue";
import {
  XMarkIcon,
  ChevronDownIcon,
  ChevronUpIcon,
} from "@heroicons/vue/20/solid";
import { announcementsApi } from "@/api/announcements";

const announcements = ref([]);
const currentIndex = ref(0);
const dismissed = ref(false);
const expanded = ref(false);
const loaded = ref(false);

const typeColors = {
  info: "bg-brand-600 text-white",
  warning: "bg-amber-500 text-white",
  success: "bg-emerald-600 text-white",
  urgent: "bg-red-600 text-white",
};

const current = () => announcements.value[currentIndex.value];

onMounted(async () => {
  try {
    const { data } = await announcementsApi.list({ limit: 5 });
    announcements.value = data;
  } catch {
    // Silently fail — announcements are non-critical
  } finally {
    loaded.value = true;
  }
});

function dismiss() {
  dismissed.value = true;
}

function next() {
  expanded.value = false;
  currentIndex.value = (currentIndex.value + 1) % announcements.value.length;
}

function toggleExpand() {
  expanded.value = !expanded.value;
}
</script>

<template>
  <div
    v-if="loaded && announcements.length > 0 && !dismissed"
    class="relative z-50"
  >
    <div
      :class="typeColors[current()?.type] ?? typeColors.info"
      class="transition-colors"
    >
      <!-- Compact title bar -->
      <div class="px-4 py-2.5 text-center text-sm font-medium">
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-3">
          <p class="flex-1 truncate font-bold">
            {{ current()?.title }}
          </p>

          <button
            v-if="current()?.content"
            type="button"
            class="shrink-0 flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-bold opacity-80 hover:opacity-100 transition-opacity"
            @click="toggleExpand"
          >
            {{ expanded ? "Less" : "More" }}
            <component
              :is="expanded ? ChevronUpIcon : ChevronDownIcon"
              class="size-3.5"
            />
          </button>

          <button
            v-if="announcements.length > 1"
            type="button"
            class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold opacity-80 hover:opacity-100 transition-opacity"
            @click="next"
          >
            {{ currentIndex + 1 }}/{{ announcements.length }} →
          </button>

          <button
            type="button"
            class="shrink-0 rounded-full p-0.5 opacity-70 hover:opacity-100 transition-opacity"
            aria-label="Dismiss announcement"
            @click="dismiss"
          >
            <XMarkIcon class="size-4" />
          </button>
        </div>
      </div>

      <!-- Expanded rich-text content -->
      <Transition name="slide">
        <div
          v-if="expanded && current()?.content"
          class="border-t border-white/20 bg-black/10 px-4 py-4"
        >
          <div
            class="announcement-content mx-auto max-w-3xl text-sm leading-relaxed opacity-95"
            v-html="current().content"
          />
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
/* Rich-text content from the admin editor */
.announcement-content :deep(p) {
  margin-bottom: 0.5rem;
}
.announcement-content :deep(p:last-child) {
  margin-bottom: 0;
}
.announcement-content :deep(strong) {
  font-weight: 700;
}
.announcement-content :deep(br) {
  display: block;
  content: "";
  margin-top: 0.25rem;
}

/* Expand / collapse transition */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
  overflow: hidden;
}
.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
}
.slide-enter-to,
.slide-leave-from {
  max-height: 20rem;
  opacity: 1;
}
</style>
