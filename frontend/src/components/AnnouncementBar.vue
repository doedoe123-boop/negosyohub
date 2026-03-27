<script setup>
import { ref, onMounted } from "vue";
import {
  XMarkIcon,
  ChevronDownIcon,
  ChevronUpIcon,
} from "@heroicons/vue/20/solid";
import { announcementsApi } from "@/api/announcements";
import { sanitizeHtml } from "@/utils/sanitizeHtml";

const announcements = ref([]);
const currentIndex = ref(0);
const dismissed = ref(false);
const showModal = ref(false);
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
  showModal.value = false;
  currentIndex.value = (currentIndex.value + 1) % announcements.value.length;
}

function toggleModal() {
  showModal.value = !showModal.value;
}

function sanitizedContent(content) {
  return sanitizeHtml(content);
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
            class="shrink-0 flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-bold opacity-80 hover:opacity-100 transition-opacity underline underline-offset-2"
            @click="toggleModal"
          >
            Read More
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

    </div>

    <!-- Modal for rich-text content -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showModal && current()?.content"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
          @click.self="toggleModal"
        >
          <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 p-4 sm:px-6">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Announcement</h3>
              <button
                type="button"
                class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition"
                @click="toggleModal"
              >
                <XMarkIcon class="size-5" />
              </button>
            </div>
            
            <!-- Modal Body -->
            <div class="max-h-[70vh] overflow-y-auto p-4 sm:p-6">
              <div
                class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300"
                v-html="sanitizedContent(current().content)"
              />
            </div>
            
            <!-- Modal Footer -->
            <div class="border-t border-slate-100 dark:border-slate-700 p-4 sm:px-6 text-right">
              <button
                type="button"
                class="btn-primary inline-flex items-center rounded-xl px-4 py-2 text-sm font-bold"
                @click="toggleModal"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* Modal fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
