<script setup>
import { 
  ShieldCheckIcon, 
  MapIcon, 
  HeartIcon,
  FlagIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import { RouterLink } from "vue-router";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { useAppI18n } from "@/i18n";

const { t } = useAppI18n();

useSeoMeta(() => ({
  title: t("about.heroTitle"),
  description: t("about.heroSubtitle"),
}));
</script>

<template>
  <main class="flex-grow bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen flex flex-col pb-20">
    <div class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6">
      
      <!-- Hero Section -->
      <section class="py-8">
        <div 
          class="relative flex min-h-[480px] flex-col gap-6 bg-cover bg-center bg-no-repeat rounded-3xl items-center justify-center p-8 text-center overflow-hidden shadow-sm" 
          style='background-image: linear-gradient(rgba(15, 32, 68, 0.7) 0%, rgba(15, 32, 68, 0.95) 100%), url("https://lh3.googleusercontent.com/aida-public/AB6AXuCVYFVV12BXjWFsHhDHBR_4pPKXow5pYvs8w50w8UYJNXRyoF5rGaWARGgwImglyxoz_Dkph9D4hHIOgtVZMDseyk6ESrXIrIk6AJLKu8iBmnBhzPhlHxDoi89lyonl4Fmo5bQj1S7y_JupDbNRR1UC54Ns-BSk2Ve-EHx0iZmAy0JJ6GWNM042J0SiXU0Lv-zKhALtIGk7rSdZvpfx5nHpAkLKZJODh9_ClunrKQ8UpVE9oRsrxsJ4II5w1fRjpmaQdpg62-cXMqs");'
        >
          <div class="flex flex-col gap-4 max-w-2xl relative z-10">
            <h1 class="text-white text-5xl font-black leading-tight tracking-tight sm:text-6xl">
              {{ t("about.heroTitle") }}
            </h1>
            <p class="text-brand-400 text-lg font-bold tracking-widest uppercase">{{ t("about.heroEyebrow") }}</p>
            <h2 class="text-slate-200 text-lg font-medium leading-relaxed sm:text-xl mt-4">
              {{ t("about.heroSubtitle") }}
            </h2>
          </div>
          <RouterLink
            to="/stores"
            class="relative z-10 mt-6 flex min-w-[160px] cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 px-8 bg-brand-500 text-white text-base font-bold shadow-lg transition hover:bg-brand-600 hover:scale-[1.02] active:scale-95"
          >
            {{ t("about.heroCta") }}
          </RouterLink>
        </div>
      </section>

      <!-- Our Mission Section -->
      <section class="py-12">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-10 sm:p-12 shadow-sm relative overflow-hidden">
          <div class="absolute -right-10 -top-10 size-40 rounded-full bg-brand-500/10 blur-3xl"></div>
          
          <div class="flex items-center gap-4 mb-6 relative z-10">
            <div class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400">
              <FlagIcon class="size-8 stroke-2" />
            </div>
            <h2 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">{{ t("about.missionTitle") }}</h2>
          </div>
          <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed relative z-10 max-w-4xl">
            {{ t("about.missionBody") }}
          </p>
        </div>
      </section>

      <!-- Our Vision Section -->
      <section class="py-16">
        <div class="max-w-4xl mx-auto text-center flex flex-col items-center">
          <div class="flex size-16 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 mb-6">
            <EyeIcon class="size-8 stroke-2" />
          </div>
          <h2 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight mb-8">{{ t("about.visionTitle") }}</h2>
          <p class="text-slate-600 dark:text-slate-300 text-2xl leading-relaxed italic font-medium px-4">
            {{ t("about.visionBody") }}
          </p>
        </div>
      </section>

      <!-- Why Choose Us Section -->
      <section class="py-16">
        <div class="text-center mb-12">
          <h2 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">{{ t("about.whyTitle") }}</h2>
          <p class="mt-4 text-slate-500">{{ t("about.whySubtitle") }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Card 1 -->
          <div class="bg-white dark:bg-slate-900/50 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 flex flex-col gap-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="size-14 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
              <ShieldCheckIcon class="size-7 stroke-2" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ t("about.trustedSellersTitle") }}</h3>
            <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed">
              {{ t("about.trustedSellersBody") }}
            </p>
          </div>
          
          <!-- Card 2 -->
          <div class="bg-white dark:bg-slate-900/50 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 flex flex-col gap-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="size-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
              <MapIcon class="size-7 stroke-2" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ t("about.allInOneTitle") }}</h3>
            <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed">
              {{ t("about.allInOneBody") }}
            </p>
          </div>
          
          <!-- Card 3 -->
          <div class="bg-white dark:bg-slate-900/50 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 flex flex-col gap-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="size-14 rounded-2xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-400">
              <HeartIcon class="size-7 stroke-2" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ t("about.proudlyFilipinoTitle") }} 🇵🇭</h3>
            <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed">
              {{ t("about.proudlyFilipinoBody") }}
            </p>
          </div>
        </div>
      </section>

      <!-- Call to Action -->
      <section class="py-16">
        <div class="bg-[#0f1f43] text-white rounded-[2rem] p-12 text-center flex flex-col items-center gap-6 relative overflow-hidden">
          <div class="absolute inset-0 z-0">
            <div class="absolute -left-1/4 -top-24 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl filter"></div>
            <div class="absolute -bottom-24 -right-1/4 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl filter"></div>
          </div>
          
          <div class="relative z-10 flex flex-col items-center gap-6">
            <h2 class="text-3xl font-bold sm:text-4xl">{{ t("about.ctaTitle") }}</h2>
            <p class="text-slate-300 max-w-xl text-lg">{{ t("about.ctaBody") }}</p>
            <div class="flex flex-wrap items-center justify-center gap-4 mt-4">
              <a href="/register/sector" class="bg-brand-500 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-brand-600 transition-colors shadow-lg">{{ t("about.ctaPrimary") }}</a>
              <RouterLink to="/faq" class="border border-white/20 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-white/10 transition-colors">{{ t("about.ctaSecondary") }}</RouterLink>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>
</template>
