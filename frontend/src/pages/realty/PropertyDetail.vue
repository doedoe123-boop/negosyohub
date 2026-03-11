<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import {
  ChevronRightIcon,
  MapPinIcon,
  HomeModernIcon,
  ArrowLeftIcon,
  EyeIcon,
  PhoneIcon,
  UserCircleIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ShieldCheckIcon,
  LockClosedIcon,
  ShareIcon,
  DocumentArrowDownIcon,
  VideoCameraIcon,
  GlobeAltIcon,
  BuildingOffice2Icon,
  StarIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarSolid } from "@heroicons/vue/24/solid";
import { propertiesApi } from "@/api/properties";

const route = useRoute();
const property = ref(null);
const loading = ref(true);
const error = ref(null);
const selectedImage = ref(0);
const showAllImages = ref(false);

// Inquiry form state
const inquiry = ref({ name: "", email: "", phone: "", message: "" });
const inquirySubmitting = ref(false);
const inquirySuccess = ref(false);
const inquiryError = ref(null);

const listingBadgeClass = {
  for_sale: "bg-emerald-100 text-emerald-700 ring-emerald-200",
  for_rent: "bg-sky-100 text-sky-700 ring-sky-200",
  for_lease: "bg-amber-100 text-amber-700 ring-amber-200",
  pre_selling: "bg-purple-100 text-purple-700 ring-purple-200",
};

onMounted(async () => {
  try {
    const { data } = await propertiesApi.show(route.params.slug);
    // JsonResource wraps in { data: { ... } }
    property.value = data.data ?? data;
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? "Property not found."
        : "Failed to load property.";
  } finally {
    loading.value = false;
  }
});

const images = computed(() => property.value?.images ?? []);
const hasGallery = computed(() => images.value.length > 0);
const thumbnails = computed(() => images.value.slice(1, 5));

const formattedPrice = computed(() => {
  if (!property.value) return "";
  const p = property.value;
  // Use server-provided formatted_price if available
  if (p.formatted_price) return p.formatted_price;
  const formatted = parseFloat(p.price).toLocaleString("en-PH", {
    style: "currency",
    currency: p.price_currency || "PHP",
    maximumFractionDigits: 0,
  });
  return p.price_period
    ? `${formatted} / ${p.price_period.replace("_", " ")}`
    : formatted;
});

const monthlyEstimate = computed(() => {
  if (!property.value?.price) return null;
  const price = parseFloat(property.value.price);
  if (isNaN(price) || property.value.listing_type !== "for_sale") return null;
  // Simple Pag-IBIG estimate: ~20yr at 6.5%
  const monthly = price * 0.005 * 1.065;
  return monthly.toLocaleString("en-PH", { maximumFractionDigits: 0 });
});

const fullAddress = computed(() => {
  if (!property.value) return "";
  return (
    property.value.full_address ||
    [
      property.value.address_line,
      property.value.barangay,
      property.value.city,
      property.value.province,
    ]
      .filter(Boolean)
      .join(", ")
  );
});

const ratingStars = computed(() => {
  const rating = property.value?.average_rating ?? 0;
  return {
    full: Math.floor(rating),
    half: rating % 1 >= 0.5,
    empty: 5 - Math.ceil(rating),
    value: rating,
    count: property.value?.review_count ?? 0,
  };
});

const publishedAgo = computed(() => {
  if (!property.value?.published_at) return null;
  const diff = Date.now() - new Date(property.value.published_at).getTime();
  const days = Math.floor(diff / 86400000);
  if (days === 0) return "Today";
  if (days === 1) return "1 day ago";
  if (days < 30) return `${days} days ago`;
  const months = Math.floor(days / 30);
  return months === 1 ? "1 month ago" : `${months} months ago`;
});

async function submitInquiry() {
  inquiryError.value = null;
  inquirySubmitting.value = true;
  try {
    await propertiesApi.submitInquiry(route.params.slug, inquiry.value);
    inquirySuccess.value = true;
    inquiry.value = { name: "", email: "", phone: "", message: "" };
  } catch {
    inquiryError.value = "Failed to send inquiry. Please try again.";
  } finally {
    inquirySubmitting.value = false;
  }
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href);
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- ── Skeleton ──────────────────────────────────────────────────── -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-[420px] w-full bg-slate-200" />
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
          <div class="space-y-4">
            <div class="h-5 w-32 rounded-full bg-slate-200" />
            <div class="h-8 w-2/3 rounded-lg bg-slate-200" />
            <div class="h-4 w-1/3 rounded bg-slate-100" />
            <div class="h-12 w-1/4 rounded-lg bg-slate-200" />
            <div class="h-28 rounded-2xl bg-slate-100" />
            <div class="h-4 w-full rounded bg-slate-100" />
            <div class="h-4 w-5/6 rounded bg-slate-100" />
          </div>
          <div class="h-[500px] rounded-2xl bg-slate-100" />
        </div>
      </div>
    </div>

    <!-- ── Error ─────────────────────────────────────────────────────── -->
    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6"
    >
      <div
        class="mx-auto mb-6 flex size-20 items-center justify-center rounded-full bg-slate-100"
      >
        <HomeModernIcon class="size-10 text-slate-400" />
      </div>
      <p class="text-xl font-semibold text-slate-700">{{ error }}</p>
      <p class="mt-2 text-sm text-slate-400">
        The listing may have been removed or the link is incorrect.
      </p>
      <RouterLink
        to="/properties"
        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors"
      >
        <ArrowLeftIcon class="size-4" /> Back to Properties
      </RouterLink>
    </div>

    <!-- ── Property ──────────────────────────────────────────────────── -->
    <template v-else-if="property">
      <!-- ─ Gallery Hero ──────────────────────────────────────────────── -->
      <div class="relative bg-slate-900">
        <!-- Main + thumbnails grid -->
        <div class="mx-auto max-w-[1400px] px-4 pt-6 pb-6 sm:px-6 lg:px-8">
          <div
            class="grid gap-3 lg:grid-cols-[2.5fr_1.5fr]"
            style="height: 520px"
          >
            <!-- Primary image -->
            <div
              class="relative overflow-hidden rounded-3xl bg-slate-800 shadow-sm"
            >
              <img
                v-if="hasGallery"
                :src="images[selectedImage]"
                :alt="property.title"
                class="h-full w-full object-cover transition-all duration-500"
              />
              <div
                v-else
                class="flex h-full items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900"
              >
                <HomeModernIcon class="size-24 text-slate-700" />
              </div>

              <!-- Back pill -->
              <RouterLink
                to="/properties"
                class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full bg-black/50 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-black/70"
              >
                <ArrowLeftIcon class="size-3.5" />
                Properties
              </RouterLink>

              <!-- Listing badge -->
              <span
                class="absolute right-3 top-3 rounded-full px-3 py-1 text-xs font-bold shadow ring-1"
                :class="
                  listingBadgeClass[property.listing_type] ??
                  'bg-slate-100 text-slate-600 ring-slate-200'
                "
              >
                {{ property.listing_type_label ?? property.listing_type }}
              </span>

              <!-- Photo count -->
              <button
                v-if="images.length > 1"
                class="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-black/80"
                @click="showAllImages = !showAllImages"
              >
                📷 View all {{ images.length }} photos
              </button>
            </div>

            <!-- Thumbnail 2×2 grid -->
            <div
              v-if="thumbnails.length"
              class="hidden lg:grid grid-cols-2 gap-3"
            >
              <button
                v-for="(img, i) in thumbnails"
                :key="i"
                class="relative overflow-hidden rounded-2xl bg-slate-800 ring-2 transition-all shadow-sm"
                :class="
                  selectedImage === i + 1
                    ? 'ring-emerald-400'
                    : 'ring-transparent hover:ring-white/30'
                "
                @click="selectedImage = i + 1"
              >
                <img
                  :src="img"
                  class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                />
                <!-- Overlay for 4th thumb if more images -->
                <div
                  v-if="i === 3 && images.length > 5"
                  class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px] text-lg font-bold text-white tracking-wide"
                >
                  +{{ images.length - 5 }}
                </div>
              </button>
              <!-- Pad with empty slots if fewer than 4 thumbnails -->
              <div
                v-for="n in Math.max(0, 4 - thumbnails.length)"
                :key="`pad-${n}`"
                class="rounded-2xl bg-slate-800"
              />
            </div>
          </div>

          <!-- Thumbnail strip (mobile / overflow) -->
          <div
            v-if="images.length > 1"
            class="mt-2 flex gap-1.5 overflow-x-auto pb-1 lg:hidden"
          >
            <button
              v-for="(img, i) in images"
              :key="i"
              class="size-14 shrink-0 overflow-hidden rounded-lg border-2 transition-all"
              :class="
                selectedImage === i
                  ? 'border-emerald-400 opacity-100'
                  : 'border-transparent opacity-50 hover:opacity-75'
              "
              @click="selectedImage = i"
            >
              <img :src="img" class="h-full w-full object-cover" />
            </button>
          </div>
        </div>
      </div>

      <!-- ─ Breadcrumb ─────────────────────────────────────────────────── -->
      <div class="border-b border-slate-100 bg-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
          <nav
            class="flex items-center gap-1.5 py-3 text-xs text-slate-400"
            aria-label="Breadcrumb"
          >
            <RouterLink to="/" class="transition-colors hover:text-emerald-600"
              >Home</RouterLink
            >
            <ChevronRightIcon class="size-3" />
            <RouterLink
              to="/properties"
              class="transition-colors hover:text-emerald-600"
              >Properties</RouterLink
            >
            <ChevronRightIcon class="size-3" />
            <span class="max-w-xs truncate text-slate-600">{{
              property.title
            }}</span>
          </nav>
        </div>
      </div>

      <!-- ─ Main Layout ────────────────────────────────────────────────── -->
      <div class="mx-auto max-w-[1240px] px-4 py-6 lg:py-10 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-[1fr_380px]">
          <!-- LEFT: Content ─────────────────────────────────────────── -->
          <div class="flex flex-col gap-6">
            <!-- Header Section -->
            <section class="flex flex-col gap-2">
              <!-- Type chip + views + published -->
              <div
                class="flex flex-wrap items-center justify-between gap-2 mb-1"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="rounded px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ring-1"
                    :class="
                      listingBadgeClass[property.listing_type] ??
                      'bg-slate-100 text-slate-600 ring-slate-200'
                    "
                  >
                    {{ property.listing_type_label ?? property.listing_type }}
                  </span>
                  <span
                    class="rounded bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-sky-600 ring-1 ring-sky-200"
                  >
                    {{ property.property_type_label ?? property.property_type }}
                  </span>
                  <span
                    v-if="property.is_featured"
                    class="rounded bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 ring-1 ring-amber-200"
                  >
                    Featured
                  </span>
                </div>
                <div class="flex items-center gap-3">
                  <span v-if="publishedAgo" class="text-xs text-slate-400"
                    >Listed {{ publishedAgo }}</span
                  >
                  <span
                    v-if="property.views_count"
                    class="flex items-center gap-1 text-xs font-semibold text-slate-400"
                  >
                    <EyeIcon class="size-4" />
                    {{ property.views_count.toLocaleString() }} views
                  </span>
                </div>
              </div>

              <!-- Title -->
              <h1
                class="text-3xl font-extrabold leading-tight text-[#0F2044] sm:text-4xl lg:text-[40px]"
              >
                {{ property.title }}
              </h1>

              <!-- Location -->
              <p
                v-if="fullAddress"
                class="flex items-center gap-1.5 text-[15px] font-medium text-slate-500 mt-1"
              >
                <MapPinIcon class="size-5 shrink-0 text-slate-400" />
                {{ fullAddress }}
              </p>

              <!-- Development badge -->
              <p
                v-if="property.development"
                class="flex items-center gap-1.5 text-sm text-slate-500"
              >
                <BuildingOffice2Icon class="size-4 shrink-0 text-indigo-400" />
                {{ property.development.name }}
                <span
                  v-if="property.development.developer_name"
                  class="text-slate-400"
                  >by {{ property.development.developer_name }}</span
                >
              </p>

              <!-- Unit info for condos -->
              <p
                v-if="property.unit_number || property.unit_floor"
                class="text-sm text-slate-500"
              >
                <span v-if="property.unit_floor"
                  >Floor {{ property.unit_floor }}</span
                >
                <span v-if="property.unit_number && property.unit_floor">
                  ·
                </span>
                <span v-if="property.unit_number"
                  >Unit {{ property.unit_number }}</span
                >
              </p>

              <!-- Price block -->
              <div class="mt-3 flex flex-wrap items-end gap-3">
                <p
                  class="text-[34px] font-black tracking-tight text-[#F95D2F] leading-none"
                >
                  {{ formattedPrice }}
                </p>
              </div>
            </section>

            <!-- Pag-IBIG monthly estimate -->
            <div
              v-if="monthlyEstimate"
              class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white px-4 py-3 text-sm shadow-sm"
            >
              <span class="text-base">🏦</span>
              <span class="text-slate-600">
                Est.
                <strong class="text-slate-900"
                  >₱{{ monthlyEstimate }}/mo</strong
                >
                via Pag-IBIG
              </span>
              <span class="ml-auto text-xs text-slate-400">~20yr @ 6.5%</span>
            </div>

            <!-- ═══ BENTO BOX: Key Specs ══════════════════════════════ -->
            <section class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <!-- Large: Price -->
              <div
                class="col-span-2 sm:col-span-1 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 ring-1 ring-emerald-200/60"
              >
                <p
                  class="text-xs font-semibold uppercase tracking-wider text-emerald-600/70 mb-1"
                >
                  Price
                </p>
                <p class="text-2xl font-black text-emerald-800 leading-tight">
                  {{ formattedPrice }}
                </p>
                <p v-if="monthlyEstimate" class="mt-1 text-xs text-emerald-600">
                  ~₱{{ monthlyEstimate }}/mo
                </p>
              </div>
              <!-- Large: Floor Area -->
              <div
                v-if="property.floor_area"
                class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1"
                >
                  Floor Area
                </p>
                <p class="text-2xl font-black text-[#0F2044]">
                  {{ property.floor_area
                  }}<span class="text-sm font-semibold text-slate-400 ml-1"
                    >sqm</span
                  >
                </p>
              </div>
              <!-- Large: Bedrooms -->
              <div
                v-if="property.bedrooms != null"
                class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1"
                >
                  Bedrooms
                </p>
                <p class="text-2xl font-black text-[#0F2044]">
                  {{ property.bedrooms
                  }}<span class="text-sm font-semibold text-slate-400 ml-1"
                    >beds</span
                  >
                </p>
              </div>
              <!-- Small: Bathrooms -->
              <div
                v-if="property.bathrooms != null"
                class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5"
                >
                  Baths
                </p>
                <p class="text-lg font-bold text-[#0F2044]">
                  {{ property.bathrooms }}
                </p>
              </div>
              <!-- Small: Garage -->
              <div
                v-if="property.garage_spaces != null"
                class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5"
                >
                  Garage
                </p>
                <p class="text-lg font-bold text-[#0F2044]">
                  {{ property.garage_spaces }}
                </p>
              </div>
              <!-- Small: Lot Area -->
              <div
                v-if="property.lot_area"
                class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5"
                >
                  Lot Area
                </p>
                <p class="text-lg font-bold text-[#0F2044]">
                  {{ property.lot_area
                  }}<span class="text-xs text-slate-400 ml-0.5">sqm</span>
                </p>
              </div>
              <!-- Small: Year Built -->
              <div
                v-if="property.year_built"
                class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5"
                >
                  Year Built
                </p>
                <p class="text-lg font-bold text-[#0F2044]">
                  {{ property.year_built }}
                </p>
              </div>
              <!-- Small: Floors -->
              <div
                v-if="property.floors"
                class="rounded-2xl bg-white p-4 ring-1 ring-slate-200/60 shadow-sm"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5"
                >
                  Floors
                </p>
                <p class="text-lg font-bold text-[#0F2044]">
                  {{ property.floors }}
                </p>
              </div>
            </section>

            <!-- About -->
            <section v-if="property.description">
              <h2 class="mb-3 text-lg font-bold text-slate-900">
                About this property
              </h2>
              <div
                class="prose prose-sm prose-slate max-w-none text-slate-600"
                v-html="property.description"
              ></div>
            </section>

            <!-- ═══ Features & Amenities (dense grid) ═════════════════ -->
            <section v-if="property.features?.length">
              <h2 class="mb-4 text-lg font-bold text-slate-900">
                Features & Amenities
              </h2>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <div
                  v-for="feature in property.features"
                  :key="feature"
                  class="flex items-center gap-2 rounded-xl bg-white px-3 py-2.5 text-sm font-medium text-slate-700 ring-1 ring-slate-100 shadow-sm"
                >
                  <CheckCircleIcon class="size-4 shrink-0 text-emerald-500" />
                  {{ feature }}
                </div>
              </div>
            </section>

            <!-- Property Details Table -->
            <section>
              <h2 class="mb-4 text-xl font-bold text-[#0F2044]">
                Property Details
              </h2>
              <div
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
              >
                <table class="w-full text-sm">
                  <tbody class="divide-y divide-slate-100">
                    <tr v-if="property.property_type">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">Type</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{
                          property.property_type_label ?? property.property_type
                        }}
                      </td>
                    </tr>
                    <tr v-if="property.listing_type" class="bg-slate-50/50">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">Listing</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{
                          property.listing_type_label ?? property.listing_type
                        }}
                      </td>
                    </tr>
                    <tr v-if="property.floor_area">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">
                        Floor Area
                      </td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.floor_area }} sqm
                      </td>
                    </tr>
                    <tr v-if="property.lot_area" class="bg-slate-50/50">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">Lot Area</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.lot_area }} sqm
                      </td>
                    </tr>
                    <tr v-if="property.year_built">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">
                        Year Built
                      </td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.year_built }}
                      </td>
                    </tr>
                    <tr v-if="property.floors" class="bg-slate-50/50">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">Floors</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.floors }}
                      </td>
                    </tr>
                    <tr v-if="property.unit_floor">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">
                        Unit Floor
                      </td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.unit_floor }}
                      </td>
                    </tr>
                    <tr v-if="property.unit_number" class="bg-slate-50/50">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">
                        Unit Number
                      </td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.unit_number }}
                      </td>
                    </tr>
                    <tr v-if="property.zip_code">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">ZIP Code</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ property.zip_code }}
                      </td>
                    </tr>
                    <tr v-if="fullAddress" class="bg-slate-50/50">
                      <td class="w-1/3 px-5 py-3.5 text-slate-500">Address</td>
                      <td class="px-5 py-3.5 font-semibold text-[#0F2044]">
                        {{ fullAddress }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Virtual Tour & Video -->
            <section v-if="property.video_url || property.virtual_tour_url">
              <h2 class="mb-3 text-lg font-bold text-slate-900">
                Tours & Media
              </h2>
              <div class="flex flex-wrap gap-3">
                <a
                  v-if="property.virtual_tour_url"
                  :href="property.virtual_tour_url"
                  target="_blank"
                  rel="noopener"
                  class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-semibold text-indigo-700 transition-colors hover:bg-indigo-100"
                >
                  <GlobeAltIcon class="size-5" />
                  Virtual Tour
                </a>
                <a
                  v-if="property.video_url"
                  :href="property.video_url"
                  target="_blank"
                  rel="noopener"
                  class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                >
                  <VideoCameraIcon class="size-5" />
                  Video Tour
                </a>
              </div>
            </section>

            <!-- Floor Plans -->
            <section v-if="property.floor_plans?.length">
              <h2 class="mb-3 text-lg font-bold text-slate-900">Floor Plans</h2>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <a
                  v-for="(plan, i) in property.floor_plans"
                  :key="i"
                  :href="plan.url || plan"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                >
                  <DocumentArrowDownIcon
                    class="size-5 shrink-0 text-slate-400"
                  />
                  {{ plan.name || `Floor Plan ${i + 1}` }}
                </a>
              </div>
            </section>

            <!-- Documents -->
            <section v-if="property.documents?.length">
              <h2 class="mb-3 text-lg font-bold text-slate-900">Documents</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a
                  v-for="(doc, i) in property.documents"
                  :key="i"
                  :href="doc.url || doc"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                >
                  <DocumentArrowDownIcon
                    class="size-5 shrink-0 text-slate-400"
                  />
                  {{ doc.name || `Document ${i + 1}` }}
                </a>
              </div>
            </section>

            <!-- Map -->
            <section v-if="property.latitude && property.longitude">
              <h2 class="mb-3 text-lg font-bold text-slate-900">Location</h2>
              <div
                class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm"
              >
                <iframe
                  :src="`https://www.openstreetmap.org/export/embed.html?bbox=${property.longitude - 0.01},${property.latitude - 0.01},${property.longitude + 0.01},${property.latitude + 0.01}&layer=mapnik&marker=${property.latitude},${property.longitude}`"
                  class="h-[300px] w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                ></iframe>
              </div>
            </section>

            <!-- Nearby Places -->
            <section v-if="property.nearby_places?.length">
              <h2 class="mb-4 text-lg font-bold text-slate-900">
                Nearby Places
              </h2>
              <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <li
                  v-for="place in property.nearby_places"
                  :key="place.name ?? place"
                  class="flex items-center justify-between rounded-xl bg-white px-4 py-3 text-sm text-slate-700 shadow-sm ring-1 ring-slate-100"
                >
                  <div class="flex items-center gap-2">
                    <MapPinIcon class="size-4 shrink-0 text-emerald-500" />
                    <span class="font-medium">{{ place.name ?? place }}</span>
                    <span
                      v-if="place.type"
                      class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500"
                      >{{ place.type }}</span
                    >
                  </div>
                  <span
                    v-if="place.distance"
                    class="text-xs font-semibold text-slate-400 shrink-0 ml-2"
                  >
                    {{ place.distance }} {{ place.distance_unit || "km" }}
                  </span>
                </li>
              </ul>
            </section>

            <!-- Share row -->
            <div class="flex items-center gap-3 border-t border-slate-100 pt-6">
              <span class="text-xs font-semibold text-slate-400"
                >Share this listing</span
              >
              <button
                class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                @click="copyLink"
              >
                <ShareIcon class="size-3.5" /> Copy link
              </button>
              <a
                :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(route.fullPath)}`"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition-colors hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600"
              >
                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"
                  ></path>
                </svg>
                Facebook
              </a>
            </div>
          </div>

          <!-- RIGHT: Sticky sidebar ──────────────────────────────────── -->
          <div class="space-y-4 lg:sticky lg:top-24 self-start">
            <!-- Agent / Inquiry Card -->
            <div
              class="rounded-3xl border border-slate-100 bg-white p-7 shadow-2xl shadow-slate-200/50"
            >
              <!-- Agent Header -->
              <div
                v-if="property.store"
                class="mb-6 border-b border-slate-100 pb-6"
              >
                <div class="flex items-start gap-4">
                  <div class="relative shrink-0">
                    <img
                      v-if="
                        property.store.agent_photo_url ||
                        property.store.logo_url
                      "
                      :src="
                        property.store.agent_photo_url ||
                        property.store.logo_url
                      "
                      class="size-16 rounded-full object-cover ring-2 ring-brand-100"
                    />
                    <UserCircleIcon
                      v-else
                      class="size-16 rounded-full text-slate-300"
                    />
                    <div
                      class="absolute -bottom-1 -right-1 flex size-5 justify-center items-center rounded-full border-2 border-white bg-brand-500 text-white"
                    >
                      <ShieldCheckIcon class="size-3" />
                    </div>
                  </div>
                  <div>
                    <h4 class="font-bold text-[#0F2044] text-lg leading-tight">
                      {{ property.store.agent_name || property.store.name }}
                    </h4>
                    <p class="text-sm text-slate-500 mt-0.5">
                      <RouterLink
                        :to="`/agent/${property.store.slug}`"
                        class="text-brand-600 hover:text-brand-700 font-semibold"
                        >{{ property.store.name }}</RouterLink
                      >
                    </p>
                    <!-- Real rating -->
                    <div class="mt-2 flex items-center gap-1">
                      <template v-for="n in 5" :key="n">
                        <StarSolid
                          v-if="n <= ratingStars.full"
                          class="size-4 text-amber-400"
                        />
                        <StarIcon v-else class="size-4 text-slate-200" />
                      </template>
                      <span class="text-xs text-slate-500 font-medium ml-1">
                        {{
                          ratingStars.value
                            ? ratingStars.value.toFixed(1)
                            : "New"
                        }}
                        <span v-if="ratingStars.count" class="text-slate-400"
                          >({{ ratingStars.count }})</span
                        >
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Call Button -->
                <a
                  v-if="property.store.phone"
                  :href="`tel:${property.store.phone}`"
                  class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-50 py-3 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-100"
                >
                  <PhoneIcon class="size-4" />
                  {{ property.store.phone }}
                </a>
              </div>

              <!-- Inquiry Form -->
              <div class="flex flex-col gap-4">
                <h3 class="font-bold text-[#0F2044]">Send an Inquiry</h3>

                <!-- Success -->
                <div
                  v-if="inquirySuccess"
                  class="flex flex-col items-center gap-2 rounded-2xl bg-[#059669]/10 p-6 text-center ring-1 ring-[#059669]/20"
                >
                  <CheckCircleIcon class="size-8 text-[#059669]" />
                  <p class="font-bold text-[#0F2044]">
                    Inquiry sent successfully!
                  </p>
                  <p class="text-xs text-slate-500">
                    The agent will be in touch with you shortly.
                  </p>
                  <button
                    class="mt-2 text-xs font-semibold text-[#059669] hover:underline"
                    @click="inquirySuccess = false"
                  >
                    Send another message
                  </button>
                </div>

                <!-- Form -->
                <form
                  v-else
                  class="flex flex-col gap-3"
                  @submit.prevent="submitInquiry"
                >
                  <input
                    v-model="inquiry.name"
                    required
                    type="text"
                    placeholder="Full Name *"
                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                  />
                  <input
                    v-model="inquiry.email"
                    required
                    type="email"
                    placeholder="Email Address *"
                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                  />
                  <input
                    v-model="inquiry.phone"
                    type="tel"
                    placeholder="Phone Number"
                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                  />
                  <textarea
                    v-model="inquiry.message"
                    rows="3"
                    placeholder="I'm interested in this property. Please contact me for a viewing."
                    class="w-full resize-none rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                  />

                  <div
                    v-if="inquiryError"
                    class="flex items-center gap-2 rounded-xl bg-red-50 p-3 text-xs text-red-600 ring-1 ring-red-100"
                  >
                    <ExclamationCircleIcon class="size-4 shrink-0" />
                    {{ inquiryError }}
                  </div>

                  <button
                    type="submit"
                    :disabled="inquirySubmitting"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-[#059669] py-4 text-sm font-bold text-white shadow-lg shadow-[#059669]/25 transition-all hover:bg-[#047857] hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                  >
                    <span
                      v-if="inquirySubmitting"
                      class="flex items-center gap-2"
                    >
                      <svg
                        class="size-5 animate-spin text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          class="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          stroke-width="4"
                        ></circle>
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                      </svg>
                      Sending…
                    </span>
                    <span v-else>Submit Inquiry</span>
                  </button>

                  <p
                    class="mt-2 flex items-center justify-center gap-1.5 text-[11px] text-slate-400 font-medium"
                  >
                    <LockClosedIcon class="size-3.5 shrink-0" />
                    Your data is protected and never shared.
                  </p>
                </form>
              </div>
            </div>

            <!-- Share mini card -->
            <div
              class="rounded-2xl border border-slate-100 bg-white px-5 py-4 shadow-sm"
            >
              <p
                class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
              >
                Share this listing
              </p>
              <div class="flex gap-2">
                <button
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50"
                  @click="copyLink"
                >
                  <ShareIcon class="size-3.5" /> Copy
                </button>
                <a
                  :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(route.fullPath)}`"
                  target="_blank"
                  rel="noopener"
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600"
                >
                  <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"
                    ></path>
                  </svg>
                  FB
                </a>
                <a
                  :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(route.fullPath)}&text=${encodeURIComponent(property.title)}`"
                  target="_blank"
                  rel="noopener"
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900"
                >
                  <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                    ></path>
                  </svg>
                  X
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
