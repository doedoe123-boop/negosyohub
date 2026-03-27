<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { advertisementsApi } from "@/api/advertisements";

const props = defineProps({
  placement: { type: String, default: "home_banner" },
});

const ads = ref([]);
const current = ref(0);
const loaded = ref(false);
let timer = null;

onMounted(async () => {
  try {
    const { data } = await advertisementsApi.list({
      placement: props.placement,
    });
    ads.value = data;
  } catch {
    // Ads are non-critical — fail silently
  } finally {
    loaded.value = true;
  }

  if (ads.value.length > 1) {
    timer = setInterval(() => {
      current.value = (current.value + 1) % ads.value.length;
    }, 6000);
  }
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <section
    v-if="loaded && ads.length > 0"
    class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6"
  >
    <p
      class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400"
    >
      Sponsored
    </p>

    <div class="relative overflow-hidden rounded-2xl bg-slate-100">
      <TransitionGroup name="ad-slide">
        <a
          v-for="(ad, i) in ads"
          v-show="i === current"
          :key="ad.id"
          :href="ad.link_url"
          target="_blank"
          rel="noopener noreferrer"
          class="block"
        >
          <img
            v-if="ad.image_url"
            :src="ad.image_url"
            :alt="ad.title"
            class="h-48 w-full object-cover sm:h-64 lg:h-72"
          />
          <div
            v-else
            class="flex h-48 items-center justify-center bg-gradient-to-r from-brand-600 to-emerald-500 sm:h-64 lg:h-72"
          >
            <div class="text-center text-white px-6">
              <div class="text-2xl font-bold">{{ ad.title }}</div>
              <div v-if="ad.description" class="mt-2 text-sm opacity-80" v-html="ad.description"></div>
            </div>
          </div>
        </a>
      </TransitionGroup>

      <!-- Dots -->
      <div
        v-if="ads.length > 1"
        class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
      >
        <button
          v-for="(_, i) in ads"
          :key="i"
          class="size-2 rounded-full transition-all"
          :class="i === current ? 'bg-white scale-125' : 'bg-white/50'"
          :aria-label="`Go to ad ${i + 1}`"
          @click="current = i"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.ad-slide-enter-active,
.ad-slide-leave-active {
  transition: opacity 0.5s ease;
}
.ad-slide-enter-from,
.ad-slide-leave-to {
  opacity: 0;
}
</style>
