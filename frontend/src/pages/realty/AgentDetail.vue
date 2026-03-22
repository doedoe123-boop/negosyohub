<script setup>
import { ref, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import {
  ChevronRightIcon,
  MapPinIcon,
  ShieldCheckIcon,
  PhoneIcon,
  HomeModernIcon,
} from "@heroicons/vue/24/outline";
import { storesApi } from "@/api/stores";

const route = useRoute();
const agent = ref(null);
const properties = ref([]);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const [agentRes, propsRes] = await Promise.all([
      storesApi.show(route.params.slug),
      storesApi.properties(route.params.slug),
    ]);
    agent.value = agentRes.data;
    properties.value = propsRes.data?.data ?? propsRes.data;
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? "Agent not found or inactive."
        : "Failed to load agent profile.";
  } finally {
    loading.value = false;
  }
});

function formatSocialUrl(url) {
  if (!url) return "#";
  return url.startsWith("http") ? url : `https://${url}`;
}

const typeLabel = {
  house: "House & Lot",
  condo: "Condominium",
  apartment: "Apartment",
  townhouse: "Townhouse",
  commercial: "Commercial Space",
  lot: "Vacant Lot",
  warehouse: "Warehouse",
  farm: "Farm / Agricultural",
};
</script>

<template>
  <div class="theme-page min-h-screen">
    <!-- Skeleton -->
    <div v-if="loading" class="animate-pulse">
      <div class="theme-skeleton h-56 w-full" />
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="relative -mt-16 flex items-end gap-5">
          <div class="theme-skeleton size-32 shrink-0 rounded-full shadow-md" />
          <div class="mb-2 space-y-2">
            <div class="theme-skeleton h-8 w-64 rounded-lg" />
            <div class="theme-skeleton h-4 w-32 rounded-full" />
          </div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6"
    >
      <div class="theme-card-muted mx-auto mb-6 flex size-20 items-center justify-center rounded-full">
        <ShieldCheckIcon class="theme-copy size-10" />
      </div>
      <p class="theme-title text-xl font-semibold">{{ error }}</p>
      <RouterLink
        to="/properties"
        class="btn-primary mt-6 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold shadow-sm transition-colors"
      >
        Back to Properties
      </RouterLink>
    </div>

    <!-- Agent Profile -->
    <template v-else>
      <!-- Premium Agent Banner -->
      <div class="relative h-48 w-full overflow-hidden sm:h-64">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0F2044] via-[#1a3673] to-[#0F2044] opacity-90" />
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay" />
      </div>

      <div class="mx-auto max-w-6xl px-4 sm:px-6 pb-20">
        <!-- Main Info Overlap -->
        <div class="theme-card relative -mt-20 mb-8 rounded-3xl p-6 shadow-sm sm:p-8">
          <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
            
            <!-- Photo -->
            <div class="shrink-0 text-center">
              <div class="theme-card relative mx-auto size-32 overflow-hidden rounded-full shadow-lg sm:size-40" style="border-width: 4px;">
                <img
                  v-if="agent.agent_photo_url || agent.logo_url"
                  :src="agent.agent_photo_url || agent.logo_url"
                  :alt="agent.agent_name || agent.name"
                  class="h-full w-full object-cover"
                />
                <div v-else class="theme-card-muted theme-copy flex h-full w-full items-center justify-center">
                  <ShieldCheckIcon class="size-16" />
                </div>
              </div>
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                  <h1 class="theme-title text-2xl font-bold sm:text-3xl">
                    {{ agent.agent_name || agent.name }}
                  </h1>
                  <p class="mt-1 text-sm font-medium text-emerald-600">
                    {{ agent.tagline || 'Verified Real Estate Professional' }}
                  </p>
                  
                  <div class="theme-copy mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="flex items-center gap-1 font-semibold text-amber-500">
                      ★★★★★ (5.0)
                    </span>
                    <span class="hidden opacity-60 sm:inline">•</span>
                    <span v-if="agent.address?.city" class="flex items-center gap-1">
                      <MapPinIcon class="size-4" />
                      {{ agent.address.city }}
                    </span>
                    <span v-if="agent.prc_license_number" class="hidden opacity-60 sm:inline">•</span>
                    <span v-if="agent.prc_license_number" class="theme-title font-medium">
                      PRC: {{ agent.prc_license_number }}
                    </span>
                  </div>
                </div>

                <!-- Contact BTN -->
                <div class="shrink-0">
                  <a
                    v-if="agent.phone"
                    :href="`tel:${agent.phone}`"
                    class="btn-primary inline-flex w-full items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-bold shadow-sm transition-all hover:shadow-emerald-600/25 hover:shadow-md sm:w-auto"
                  >
                    <PhoneIcon class="size-4" />
                    Contact Agent
                  </a>
                </div>
              </div>

              <!-- Tags -->
              <div v-if="agent.agent_specializations?.length || agent.agent_certifications?.length" class="mt-6 flex flex-wrap gap-2">
                <span
                  v-for="spec in agent.agent_specializations"
                  :key="spec"
                  class="rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200"
                >
                  {{ spec }}
                </span>
                <span
                  v-for="cert in agent.agent_certifications"
                  :key="cert"
                  class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"
                >
                  {{ cert }}
                </span>
              </div>
            </div>
          </div>
          
          <hr class="theme-divider-soft my-6" />
          
          <!-- Bio & Social Grid -->
          <div class="grid gap-8 lg:grid-cols-[1fr_300px]">
            <div>
              <h3 class="theme-title mb-2 font-bold">About the Agent</h3>
              <p class="theme-copy whitespace-pre-line text-sm leading-relaxed">
                {{ agent.agent_bio || agent.description || 'No biography provided.' }}
              </p>
            </div>
            
            <div v-if="agent.social_links && Object.entries(agent.social_links).some(([_, v]) => v)">
              <h3 class="theme-title mb-3 font-bold">Connect Online</h3>
              <div class="flex flex-wrap gap-2">
                <a v-if="agent.social_links.facebook" :href="formatSocialUrl(agent.social_links.facebook)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-blue-50 hover:text-blue-600" title="Facebook">
                  <svg class="size-4.5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"></path></svg>
                </a>
                <a v-if="agent.social_links.instagram" :href="formatSocialUrl(agent.social_links.instagram)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-pink-50 hover:text-pink-600" title="Instagram">
                  <svg class="size-4.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" /></svg>
                </a>
                <a v-if="agent.social_links.linkedin" :href="formatSocialUrl(agent.social_links.linkedin)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-sky-50 hover:text-sky-700" title="LinkedIn">
                  <svg class="size-4.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"/></svg>
                </a>
                <a v-if="agent.social_links.tiktok" :href="formatSocialUrl(agent.social_links.tiktok)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-slate-900 hover:text-white" title="TikTok">
                  <svg class="size-4.5" fill="currentColor" viewBox="0 0 448 512"><path d="M448 209.9a210.1 210.1 0 01-122.8-39.3V349.4A162.6 162.6 0 11185 188.3v89.9a72.7 72.7 0 1058.2 71.3V0h88.6c1.6 44 23.3 84.8 56.2 110z"/></svg>
                </a>
                <a v-if="agent.social_links.youtube" :href="formatSocialUrl(agent.social_links.youtube)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-red-50 hover:text-red-600" title="YouTube">
                  <svg class="size-4.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M21.582 6.186a2.686 2.686 0 00-1.884-1.898C18.037 3.84 12 3.84 12 3.84s-6.037 0-7.698.448a2.686 2.686 0 00-1.884 1.898C2 7.863 2 12 2 12s0 4.137.418 5.814a2.686 2.686 0 001.884 1.898c1.661.448 7.698.448 7.698.448s6.037 0 7.698-.448a2.686 2.686 0 001.884-1.898C22 16.137 22 12 22 12s0-4.137-.418-5.814zM9.99 15.424V8.576L15.932 12 9.99 15.424z" clip-rule="evenodd" /></svg>
                </a>
                <a v-if="agent.social_links.website" :href="formatSocialUrl(agent.social_links.website)" target="_blank" class="theme-card-muted theme-copy flex size-10 items-center justify-center rounded-xl transition-colors hover:bg-emerald-50 hover:text-emerald-600" title="Personal Website">
                  <svg class="size-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Agent Portfolio / Listed Properties -->
        <h2 class="theme-title mb-4 text-xl font-bold">Featured Properties</h2>
        
        <div v-if="properties.length === 0" class="theme-empty-state rounded-3xl py-16 text-center">
          <HomeModernIcon class="theme-copy mx-auto mb-3 size-12" />
          <p class="text-sm">This agent hasn't listed any properties yet.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <RouterLink
            v-for="prop in properties"
            :key="prop.id"
            :to="`/properties/${prop.slug}`"
            class="theme-card theme-card-hover group flex flex-col overflow-hidden rounded-3xl transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_10px_20px_-6px_rgba(16,185,129,0.15)]"
          >
            <!-- Image Area -->
            <div class="theme-card-muted relative aspect-[4/3] w-full overflow-hidden">
              <img
                v-if="prop.images && prop.images.length > 0"
                :src="prop.images[0]"
                :alt="prop.title"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
              />
              <div v-else class="theme-copy flex h-full items-center justify-center">
                <HomeModernIcon class="size-12" />
              </div>
              
              <!-- Badges -->
              <div class="absolute left-3 top-3 flex gap-2 text-[10px] font-bold uppercase tracking-wider">
                <span v-if="prop.listing_type === 'for_sale'" class="rounded-full bg-emerald-500 px-2.5 py-1 text-white shadow-sm">
                  For Sale
                </span>
                <span v-else-if="prop.listing_type === 'for_rent'" class="rounded-full bg-sky-500 px-2.5 py-1 text-white shadow-sm">
                  For Rent
                </span>
                <span v-else class="rounded-full bg-slate-800 px-2.5 py-1 text-white shadow-sm">
                  {{ prop.listing_type }}
                </span>
              </div>
            </div>

            <!-- Content Area -->
            <div class="flex flex-1 flex-col p-5">
              <p class="theme-copy mb-1 text-[11px] font-semibold uppercase tracking-widest transition-colors group-hover:text-emerald-600">
                {{ typeLabel[prop.property_type] ?? prop.property_type }}
              </p>
              
              <h3 class="theme-title line-clamp-2 min-h-[3rem] text-base font-bold leading-snug group-hover:text-emerald-700">
                {{ prop.title }}
              </h3>
              
              <p v-if="prop.city" class="theme-copy mt-2 flex items-center gap-1.5 text-xs">
                <MapPinIcon class="size-3.5 shrink-0" />
                {{ prop.city }}
              </p>

              <div class="mt-auto pt-4">
                <hr class="theme-divider-soft mb-4" />
                <div class="flex items-center justify-between">
                  <div class="theme-copy flex items-center gap-3 text-xs font-semibold">
                    <span v-if="prop.bedrooms" class="flex gap-1" title="Bedrooms">
                      🛏 {{ prop.bedrooms }}
                    </span>
                    <span v-if="prop.bathrooms" class="flex gap-1" title="Bathrooms">
                      🚿 {{ prop.bathrooms }}
                    </span>
                    <span v-if="prop.floor_area" class="theme-copy flex gap-1 font-normal">
                      {{ prop.floor_area }} sqm
                    </span>
                  </div>
                  <p class="theme-title text-base font-extrabold">
                    {{ prop.price != null ? "₱" + parseFloat(prop.price).toLocaleString("en-PH", { maximumFractionDigits: 0 }) : "—" }}
                  </p>
                </div>
              </div>
            </div>
          </RouterLink>
        </div>
      </div>
    </template>
  </div>
</template>
