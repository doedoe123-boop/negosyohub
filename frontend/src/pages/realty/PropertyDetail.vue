<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import client from "@/api/client";

const route = useRoute();
const property = ref(null);
const loading = ref(true);
const selectedImage = ref(0);
const inquiryForm = ref({ name: "", email: "", phone: "", message: "" });
const inquirySent = ref(false);

onMounted(async () => {
  try {
    const { data } = await client.get(`/api/properties/${route.params.slug}`);
    property.value = data;
  } finally {
    loading.value = false;
  }
});

async function submitInquiry() {
  await client.post(
    `/api/properties/${route.params.slug}/inquire`,
    inquiryForm.value,
  );
  inquirySent.value = true;
}
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <div v-if="loading" class="space-y-4">
      <div class="aspect-video w-full animate-pulse rounded-2xl bg-gray-100" />
    </div>

    <div v-else-if="property">
      <!-- Gallery -->
      <div class="mb-8">
        <img
          :src="property.images?.[selectedImage] ?? '/placeholder.png'"
          :alt="property.title"
          class="aspect-video w-full rounded-2xl object-cover bg-gray-100"
        />
        <div
          v-if="property.images?.length > 1"
          class="mt-2 flex gap-2 overflow-x-auto"
        >
          <button
            v-for="(img, i) in property.images"
            :key="i"
            type="button"
            class="flex-shrink-0 overflow-hidden rounded-lg border-2 transition-colors"
            :class="
              selectedImage === i ? 'border-brand-500' : 'border-transparent'
            "
            @click="selectedImage = i"
          >
            <img :src="img" class="h-16 w-24 object-cover" />
          </button>
        </div>
      </div>

      <div class="grid gap-8 lg:grid-cols-3">
        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ property.title }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 capitalize">
              {{ property.type?.replace("_", " ") }} · {{ property.city }}
            </p>
            <p class="mt-3 text-2xl font-bold text-brand-600">
              {{ property.price_formatted }}
            </p>
          </div>

          <!-- Specs -->
          <div class="grid grid-cols-3 gap-3 text-center text-sm">
            <div
              v-if="property.bedrooms"
              class="rounded-xl border bg-gray-50 py-3"
            >
              <p class="text-lg font-bold text-gray-800">
                {{ property.bedrooms }}
              </p>
              <p class="text-xs text-gray-500">Bedrooms</p>
            </div>
            <div
              v-if="property.bathrooms"
              class="rounded-xl border bg-gray-50 py-3"
            >
              <p class="text-lg font-bold text-gray-800">
                {{ property.bathrooms }}
              </p>
              <p class="text-xs text-gray-500">Bathrooms</p>
            </div>
            <div
              v-if="property.lot_area_sqm"
              class="rounded-xl border bg-gray-50 py-3"
            >
              <p class="text-lg font-bold text-gray-800">
                {{ property.lot_area_sqm }}
              </p>
              <p class="text-xs text-gray-500">Lot Area (sqm)</p>
            </div>
          </div>

          <div>
            <h2 class="mb-2 font-semibold text-gray-800">Description</h2>
            <p class="text-sm leading-relaxed text-gray-600">
              {{ property.description }}
            </p>
          </div>

          <!-- Floor Plans -->
          <div v-if="property.floor_plans?.length">
            <h2 class="mb-3 font-semibold text-gray-800">Floor Plans</h2>
            <div class="grid grid-cols-2 gap-3">
              <div
                v-for="plan in property.floor_plans"
                :key="plan.url"
                class="rounded-xl border bg-gray-50 p-3 text-xs text-gray-600"
              >
                <p class="font-medium">{{ plan.label }}</p>
                <a
                  :href="plan.url"
                  target="_blank"
                  class="mt-1 inline-block text-brand-600 hover:underline"
                  >View Plan →</a
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Inquiry form -->
        <aside class="rounded-2xl border bg-white p-5 h-fit">
          <h2 class="mb-4 font-semibold text-gray-900">Send Inquiry</h2>
          <div
            v-if="inquirySent"
            class="py-4 text-center text-sm text-green-600"
          >
            ✓ Inquiry sent! We'll get back to you soon.
          </div>
          <form v-else class="space-y-3" @submit.prevent="submitInquiry">
            <input
              v-model="inquiryForm.name"
              placeholder="Your name"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
            <input
              v-model="inquiryForm.email"
              type="email"
              placeholder="Email"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
            <input
              v-model="inquiryForm.phone"
              type="tel"
              placeholder="Phone number"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
            <textarea
              v-model="inquiryForm.message"
              rows="3"
              placeholder="Your message…"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
            <button
              type="submit"
              class="w-full rounded-xl bg-brand-500 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
            >
              Send Inquiry
            </button>
          </form>
        </aside>
      </div>
    </div>
  </div>
</template>
