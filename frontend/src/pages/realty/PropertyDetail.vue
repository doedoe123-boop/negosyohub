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
  MapIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  PhotoIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarSolid } from "@heroicons/vue/24/solid";
import { propertiesApi } from "@/api/properties";
import { openHousesApi } from "@/api/openHouses";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { reviewsApi } from "@/api/reviews";
import { useAuthStore } from "@/stores/auth";
import PhotoLightbox from "@/components/PhotoLightbox.vue";
import ReviewForm from "@/components/ReviewForm.vue";

const route = useRoute();
const auth = useAuthStore();
const property = ref(null);
const loading = ref(true);
const error = ref(null);
const selectedImage = ref(0);
const showAllImages = ref(false);
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

function openLightbox(index = 0) {
  lightboxIndex.value = index;
  lightboxOpen.value = true;
}

// Direction photos state
const visibleDirectionPhotos = ref(new Set());

function toggleDirectionPhoto(index) {
  const newSet = new Set(visibleDirectionPhotos.value);
  if (newSet.has(index)) {
    newSet.delete(index);
  } else {
    newSet.add(index);
  }
  visibleDirectionPhotos.value = newSet;
}

// Inquiry form state
const inquiry = ref({ name: "", email: "", phone: "", message: "" });
const inquirySubmitting = ref(false);
const inquirySuccess = ref(false);
const inquiryError = ref(null);
const quickMessage = ref("");
const showQuickMessage = ref(false);
const inquirySuccessMessage = ref("");
const hasInquired = ref(false);
const hasRented = ref(false);

// Open houses state
const openHouses = ref([]);
const rsvpModalOpen = ref(false);
const selectedOpenHouse = ref(null);
const rsvpForm = ref({ name: "", email: "", phone: "", notes: "" });
const rsvpSubmitting = ref(false);
const rsvpSuccess = ref(false);
const rsvpError = ref(null);

function openRsvpModal(openHouse) {
  selectedOpenHouse.value = openHouse;
  rsvpSuccess.value = false;
  rsvpError.value = null;
  if (auth.user) {
    rsvpForm.value.name = auth.user.name ?? "";
    rsvpForm.value.email = auth.user.email ?? "";
    rsvpForm.value.phone = auth.user.phone ?? "";
  }
  rsvpModalOpen.value = true;
}

async function submitRsvp() {
  if (!selectedOpenHouse.value) return;
  rsvpSubmitting.value = true;
  rsvpError.value = null;
  try {
    await openHousesApi.rsvp(selectedOpenHouse.value.id, rsvpForm.value);
    rsvpSuccess.value = true;
  } catch (e) {
    const status = e.response?.status;
    if (status === 409) {
      rsvpSuccess.value = true; // Already registered
    } else {
      rsvpError.value =
        e.response?.data?.message ?? "Failed to submit RSVP. Please try again.";
    }
  } finally {
    rsvpSubmitting.value = false;
  }
}

function closeRsvpModal() {
  rsvpModalOpen.value = false;
  rsvpForm.value = { name: "", email: "", phone: "", notes: "" };
}

function trackEvent(event) {
  propertiesApi.track(route.params.slug, event);
}

const defaultMessage = computed(() => {
  if (!property.value || !auth.user) return "";
  const name = auth.user.name;
  const contactName =
    property.value.store?.agent_name || (isRental.value ? "landlord" : "agent");
  return `Hi ${contactName}, I'm ${name} and I'm interested in your listing "${property.value.title}". I'd love to schedule a viewing or learn more about this property. Looking forward to hearing from you!`;
});

const listingBadgeClass = {
  for_sale: "bg-emerald-100 text-emerald-700 ring-emerald-200",
  for_rent: "bg-sky-100 text-sky-700 ring-sky-200",
  for_lease: "bg-amber-100 text-amber-700 ring-amber-200",
  pre_selling: "bg-purple-100 text-purple-700 ring-purple-200",
};

useSeoMeta(() => ({
  title: property.value?.seo_title || property.value?.title || null,
  description:
    property.value?.seo_description || property.value?.description || null,
  ogImage: property.value?.images?.[0] || null,
  ogType: "article",
}));

onMounted(async () => {
  try {
    const { data } = await propertiesApi.show(route.params.slug);
    // JsonResource wraps in { data: { ... } }
    property.value = data.data ?? data;

    if (property.value.has_inquired) {
      hasInquired.value = true;
    }
    if (property.value.has_rented) {
      hasRented.value = true;
    }

    // Load open houses in parallel (non-blocking)
    propertiesApi
      .openHouses(route.params.slug)
      .then((r) => {
        openHouses.value = r.data?.data ?? r.data ?? [];
      })
      .catch(() => {});
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

const isRental = computed(
  () => property.value?.store?.sector_template === "rental",
);

async function submitInquiry() {
  inquiryError.value = null;
  inquirySubmitting.value = true;
  try {
    await propertiesApi.submitInquiry(route.params.slug, inquiry.value);
    inquirySuccess.value = true;
    inquirySuccessMessage.value = isRental.value
      ? "The landlord will be in touch with you shortly."
      : "The agent will be in touch with you shortly.";
    inquiry.value = { name: "", email: "", phone: "", message: "" };
  } catch {
    inquiryError.value = "Failed to send inquiry. Please try again.";
  } finally {
    inquirySubmitting.value = false;
  }
}

async function submitQuickInquiry() {
  inquiryError.value = null;
  inquirySubmitting.value = true;
  try {
    const { data } = await propertiesApi.quickInquiry(route.params.slug, {
      message: quickMessage.value || null,
    });
    inquirySuccess.value = true;
    inquirySuccessMessage.value =
      data.message ?? "Your interest has been sent!";
    quickMessage.value = "";
    showQuickMessage.value = false;
    hasInquired.value = true;
  } catch {
    inquiryError.value = "Failed to send your interest. Please try again.";
  } finally {
    inquirySubmitting.value = false;
  }
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href);
}

const propertyReviewFormRef = ref(null);

async function submitPropertyReview(payload) {
  try {
    await reviewsApi.submitForProperty(route.params.slug, payload);
    propertyReviewFormRef.value?.onSuccess();
  } catch (e) {
    propertyReviewFormRef.value?.onError(
      e.response?.data?.message ?? "Failed to submit review. Please try again.",
    );
  }
}
</script>

<template>
  <div class="theme-page min-h-screen">
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
              class="relative overflow-hidden rounded-3xl bg-slate-800 shadow-sm cursor-pointer"
              @click="hasGallery && openLightbox(selectedImage)"
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
                @click.stop="openLightbox(0)"
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
                @click="
                  selectedImage = i + 1;
                  openLightbox(i + 1);
                "
              >
                <img
                  :src="img"
                  class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                />
                <!-- Overlay for 4th thumb if more images -->
                <div
                  v-if="i === 3 && images.length > 5"
                  class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px] text-lg font-bold text-white tracking-wide"
                  @click.stop="openLightbox(i + 1)"
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
              @click="
                selectedImage = i;
                openLightbox(i);
              "
            >
              <img :src="img" class="h-full w-full object-cover" />
            </button>
          </div>
        </div>
      </div>

      <!-- ─ Breadcrumb ─────────────────────────────────────────────────── -->
      <div class="theme-page-section border-b" style="border-color: var(--color-border)">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
          <nav
            class="theme-breadcrumb flex items-center gap-1.5 py-3 text-xs"
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
            <span class="theme-breadcrumb-current max-w-xs truncate">{{
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
                  <span v-if="publishedAgo" class="theme-copy text-xs"
                    >Listed {{ publishedAgo }}</span
                  >
                  <span
                    v-if="property.views_count"
                    class="theme-copy flex items-center gap-1 text-xs font-semibold"
                  >
                    <EyeIcon class="size-4" />
                    {{ property.views_count.toLocaleString() }} views
                  </span>
                </div>
              </div>

              <!-- Title -->
              <h1
                class="theme-title text-3xl font-extrabold leading-tight sm:text-4xl lg:text-[40px]"
              >
                {{ property.title }}
              </h1>

              <!-- Location -->
              <p
                v-if="fullAddress"
                class="theme-copy mt-1 flex items-center gap-1.5 text-[15px] font-medium"
              >
                <MapPinIcon class="theme-copy size-5 shrink-0" />
                {{ fullAddress }}
              </p>

              <!-- Development badge -->
              <p
                v-if="!isRental && property.development"
                class="theme-copy flex items-center gap-1.5 text-sm"
              >
                <BuildingOffice2Icon class="size-4 shrink-0 text-indigo-400" />
                {{ property.development.name }}
                <span
                  v-if="property.development.developer_name"
                  class="theme-copy"
                  >by {{ property.development.developer_name }}</span
                >
              </p>

              <!-- Unit info for condos -->
              <p
                v-if="property.unit_number || property.unit_floor"
                class="theme-copy text-sm"
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
              v-if="!isRental && monthlyEstimate"
              class="theme-card flex items-center gap-2 rounded-xl px-4 py-3 text-sm"
            >
              <span class="text-base">🏦</span>
              <span class="theme-copy">
                Est.
                <strong class="theme-title"
                  >₱{{ monthlyEstimate }}/mo</strong
                >
                via Pag-IBIG
              </span>
              <span class="theme-copy ml-auto text-xs">~20yr @ 6.5%</span>
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
                class="theme-card rounded-2xl p-5"
              >
                <p class="theme-copy mb-1 text-xs font-semibold uppercase tracking-wider">
                  Floor Area
                </p>
                <p class="theme-title text-2xl font-black">
                  {{ property.floor_area
                  }}<span class="theme-copy ml-1 text-sm font-semibold"
                    >sqm</span
                  >
                </p>
              </div>
              <!-- Large: Bedrooms -->
              <div
                v-if="property.bedrooms != null"
                class="theme-card rounded-2xl p-5"
              >
                <p class="theme-copy mb-1 text-xs font-semibold uppercase tracking-wider">
                  Bedrooms
                </p>
                <p class="theme-title text-2xl font-black">
                  {{ property.bedrooms
                  }}<span class="theme-copy ml-1 text-sm font-semibold"
                    >beds</span
                  >
                </p>
              </div>
              <!-- Small: Bathrooms -->
              <div
                v-if="property.bathrooms != null"
                class="theme-card rounded-2xl p-4"
              >
                <p class="theme-copy mb-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  Baths
                </p>
                <p class="theme-title text-lg font-bold">
                  {{ property.bathrooms }}
                </p>
              </div>
              <!-- Small: Garage -->
              <div
                v-if="property.garage_spaces != null"
                class="theme-card rounded-2xl p-4"
              >
                <p class="theme-copy mb-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  Garage
                </p>
                <p class="theme-title text-lg font-bold">
                  {{ property.garage_spaces }}
                </p>
              </div>
              <!-- Small: Lot Area -->
              <div
                v-if="property.lot_area && !isRental"
                class="theme-card rounded-2xl p-4"
              >
                <p class="theme-copy mb-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  Lot Area
                </p>
                <p class="theme-title text-lg font-bold">
                  {{ property.lot_area
                  }}<span class="theme-copy ml-0.5 text-xs">sqm</span>
                </p>
              </div>
              <!-- Small: Year Built -->
              <div
                v-if="property.year_built && !isRental"
                class="theme-card rounded-2xl p-4"
              >
                <p class="theme-copy mb-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  Year Built
                </p>
                <p class="theme-title text-lg font-bold">
                  {{ property.year_built }}
                </p>
              </div>
              <!-- Small: Floors -->
              <div
                v-if="property.floors && !isRental"
                class="theme-card rounded-2xl p-4"
              >
                <p class="theme-copy mb-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  Floors
                </p>
                <p class="theme-title text-lg font-bold">
                  {{ property.floors }}
                </p>
              </div>
            </section>

            <!-- About -->
            <section v-if="property.description">
              <h2 class="theme-title mb-3 text-lg font-bold">
                About this property
              </h2>
              <div
                class="prose prose-sm max-w-none"
                style="color: var(--color-text-muted)"
                v-html="property.description"
              ></div>
            </section>

            <!-- ═══ Features & Amenities (dense grid) ═════════════════ -->
            <section v-if="property.features?.length && !isRental">
              <h2 class="theme-title mb-4 text-lg font-bold">
                Features & Amenities
              </h2>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <div
                  v-for="feature in property.features"
                  :key="feature"
                  class="theme-card flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium"
                >
                  <CheckCircleIcon class="size-4 shrink-0 text-emerald-500" />
                  {{ feature }}
                </div>
              </div>
            </section>

            <!-- Property Details Table -->
            <section>
              <h2 class="theme-title mb-4 text-xl font-bold">
                Property Details
              </h2>
              <div class="theme-card overflow-hidden rounded-2xl">
                <table class="theme-table text-sm">
                  <tbody>
                    <tr v-if="property.property_type">
                      <td class="theme-copy w-1/3 px-5 py-3.5">Type</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{
                          property.property_type_label ?? property.property_type
                        }}
                      </td>
                    </tr>
                    <tr
                      v-if="property.listing_type"
                      style="background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)"
                    >
                      <td class="theme-copy w-1/3 px-5 py-3.5">Listing</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{
                          property.listing_type_label ?? property.listing_type
                        }}
                      </td>
                    </tr>
                    <tr v-if="property.floor_area">
                      <td class="theme-copy w-1/3 px-5 py-3.5">
                        Floor Area
                      </td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.floor_area }} sqm
                      </td>
                    </tr>
                    <tr
                      v-if="property.lot_area"
                      style="background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)"
                    >
                      <td class="theme-copy w-1/3 px-5 py-3.5">Lot Area</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.lot_area }} sqm
                      </td>
                    </tr>
                    <tr v-if="property.year_built">
                      <td class="theme-copy w-1/3 px-5 py-3.5">
                        Year Built
                      </td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.year_built }}
                      </td>
                    </tr>
                    <tr
                      v-if="property.floors"
                      style="background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)"
                    >
                      <td class="theme-copy w-1/3 px-5 py-3.5">Floors</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.floors }}
                      </td>
                    </tr>
                    <tr v-if="property.unit_floor">
                      <td class="theme-copy w-1/3 px-5 py-3.5">
                        Unit Floor
                      </td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.unit_floor }}
                      </td>
                    </tr>
                    <tr
                      v-if="property.unit_number"
                      style="background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)"
                    >
                      <td class="theme-copy w-1/3 px-5 py-3.5">
                        Unit Number
                      </td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.unit_number }}
                      </td>
                    </tr>
                    <tr v-if="property.zip_code">
                      <td class="theme-copy w-1/3 px-5 py-3.5">ZIP Code</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ property.zip_code }}
                      </td>
                    </tr>
                    <tr
                      v-if="fullAddress"
                      style="background-color: color-mix(in srgb, var(--color-surface-muted) 72%, transparent)"
                    >
                      <td class="theme-copy w-1/3 px-5 py-3.5">Address</td>
                      <td class="theme-title px-5 py-3.5 font-semibold">
                        {{ fullAddress }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Virtual Tour & Video -->
            <section v-if="property.video_url || property.virtual_tour_url">
              <h2 class="theme-title mb-3 text-lg font-bold">
                Tours & Media
              </h2>
              <div class="flex flex-wrap gap-3">
                <a
                  v-if="property.virtual_tour_url"
                  :href="property.virtual_tour_url"
                  target="_blank"
                  rel="noopener"
                  class="theme-card inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700"
                >
                  <GlobeAltIcon class="size-5" />
                  Virtual Tour
                </a>
                <a
                  v-if="property.video_url"
                  :href="property.video_url"
                  target="_blank"
                  rel="noopener"
                  class="theme-card inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-emerald-600 transition-colors hover:text-emerald-500"
                >
                  <VideoCameraIcon class="size-5" />
                  Video Tour
                </a>
              </div>
            </section>

            <!-- Floor Plans -->
            <section v-if="property.floor_plans?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">Floor Plans</h2>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <a
                  v-for="(plan, i) in property.floor_plans"
                  :key="i"
                  :href="plan.url || plan"
                  target="_blank"
                  rel="noopener"
                  class="theme-card flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium transition-colors"
                >
                  <DocumentArrowDownIcon class="theme-copy size-5 shrink-0" />
                  {{ plan.name || `Floor Plan ${i + 1}` }}
                </a>
              </div>
            </section>

            <!-- Documents -->
            <section v-if="property.documents?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">Documents</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a
                  v-for="(doc, i) in property.documents"
                  :key="i"
                  :href="doc.url || doc"
                  target="_blank"
                  rel="noopener"
                  class="theme-card flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium transition-colors"
                >
                  <DocumentArrowDownIcon class="theme-copy size-5 shrink-0" />
                  {{ doc.name || `Document ${i + 1}` }}
                </a>
              </div>
            </section>

            <!-- Map -->
            <section v-if="property.latitude && property.longitude">
              <h2 class="theme-title mb-3 text-lg font-bold">Location</h2>
              <div class="theme-card overflow-hidden rounded-2xl shadow-sm">
                <iframe
                  :src="`https://www.openstreetmap.org/export/embed.html?bbox=${property.longitude - 0.01},${property.latitude - 0.01},${property.longitude + 0.01},${property.latitude + 0.01}&layer=mapnik&marker=${property.latitude},${property.longitude}`"
                  class="h-[300px] w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                ></iframe>
              </div>
            </section>

            <!-- Open Houses -->
            <section v-if="openHouses.length">
              <h2 class="theme-title mb-4 text-lg font-bold">Open Houses</h2>
              <ul class="flex flex-col gap-3">
                <li
                  v-for="oh in openHouses"
                  :key="oh.id"
                  class="theme-card flex items-start justify-between gap-4 rounded-2xl p-4 shadow-sm"
                >
                  <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                      <span class="theme-title text-sm font-semibold">{{
                        oh.title
                      }}</span>
                      <span
                        v-if="oh.is_virtual"
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-600"
                        >Virtual</span
                      >
                    </div>
                    <p class="theme-copy text-xs">
                      {{
                        new Date(oh.event_date).toLocaleDateString("en-PH", {
                          weekday: "long",
                          year: "numeric",
                          month: "long",
                          day: "numeric",
                        })
                      }}
                      <template v-if="oh.start_time">
                        &middot; {{ oh.start_time
                        }}<template v-if="oh.end_time">
                          – {{ oh.end_time }}</template
                        >
                      </template>
                    </p>
                    <p
                      v-if="oh.description"
                      class="theme-copy mt-0.5 text-xs"
                    >
                      {{ oh.description }}
                    </p>
                  </div>
                  <button
                    class="btn-primary shrink-0 rounded-xl px-4 py-2 text-xs font-bold transition-colors"
                    @click="openRsvpModal(oh)"
                  >
                    RSVP
                  </button>
                </li>
              </ul>

              <!-- RSVP Modal -->
              <Teleport to="body">
                <div
                  v-if="rsvpModalOpen"
                  class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
                  @click.self="closeRsvpModal"
                >
                  <div class="theme-modal w-full max-w-md rounded-2xl p-6 shadow-xl">
                    <h3 class="theme-title mb-1 text-lg font-bold">
                      RSVP for Open House
                    </h3>
                    <p class="theme-copy mb-4 text-sm">
                      {{ selectedOpenHouse?.title }}
                    </p>

                    <div
                      v-if="rsvpSuccess"
                      class="flex flex-col items-center gap-3 py-4 text-center"
                    >
                      <CheckCircleIcon class="size-10 text-emerald-500" />
                      <p class="theme-title font-bold">You're registered!</p>
                      <p class="theme-copy text-sm">
                        We'll see you at the open house.
                      </p>
                      <button
                        class="btn-secondary mt-2 rounded-xl px-5 py-2 text-sm font-semibold"
                        @click="closeRsvpModal"
                      >
                        Close
                      </button>
                    </div>

                    <form
                      v-else
                      class="flex flex-col gap-3"
                      @submit.prevent="submitRsvp"
                    >
                      <div>
                        <label
                          class="theme-copy mb-1 block text-xs font-semibold"
                          >Full Name *</label
                        >
                        <input
                          v-model="rsvpForm.name"
                          required
                          type="text"
                          class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                          placeholder="Your full name"
                        />
                      </div>
                      <div>
                        <label
                          class="theme-copy mb-1 block text-xs font-semibold"
                          >Email *</label
                        >
                        <input
                          v-model="rsvpForm.email"
                          required
                          type="email"
                          class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                          placeholder="you@email.com"
                        />
                      </div>
                      <div>
                        <label
                          class="theme-copy mb-1 block text-xs font-semibold"
                          >Phone</label
                        >
                        <input
                          v-model="rsvpForm.phone"
                          type="tel"
                          class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                          placeholder="+63 9xx xxx xxxx"
                        />
                      </div>
                      <div>
                        <label
                          class="theme-copy mb-1 block text-xs font-semibold"
                          >Notes</label
                        >
                        <textarea
                          v-model="rsvpForm.notes"
                          rows="2"
                          class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                          placeholder="Any questions or notes..."
                        ></textarea>
                      </div>
                      <p
                        v-if="rsvpError"
                        class="rounded-xl bg-red-50 px-3 py-2 text-xs text-red-600"
                      >
                        {{ rsvpError }}
                      </p>
                      <div class="flex gap-2 pt-1">
                        <button
                          type="button"
                          class="btn-secondary flex-1 rounded-xl py-2.5 text-sm font-semibold"
                          @click="closeRsvpModal"
                        >
                          Cancel
                        </button>
                        <button
                          type="submit"
                          :disabled="rsvpSubmitting"
                          class="btn-primary flex-1 rounded-xl py-2.5 text-sm font-bold transition-colors disabled:opacity-60"
                        >
                          {{
                            rsvpSubmitting ? "Registering..." : "Confirm RSVP"
                          }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </Teleport>
            </section>

            <!-- Nearby Places -->
            <section v-if="property.nearby_places?.length">
              <h2 class="theme-title mb-4 text-lg font-bold">
                Nearby Places
              </h2>
              <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <li
                  v-for="place in property.nearby_places"
                  :key="place.name ?? place"
                  class="theme-card flex items-center justify-between rounded-xl px-4 py-3 text-sm shadow-sm"
                >
                  <div class="flex items-center gap-2">
                    <MapPinIcon class="size-4 shrink-0 text-emerald-500" />
                    <span class="font-medium">{{ place.name ?? place }}</span>
                    <span
                      v-if="place.type"
                      class="theme-copy rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                      style="background-color: var(--color-surface-muted)"
                      >{{ place.type }}</span
                    >
                  </div>
                  <span
                    v-if="place.distance"
                    class="theme-copy ml-2 shrink-0 text-xs font-semibold"
                  >
                    {{ place.distance }} {{ place.distance_unit || "km" }}
                  </span>
                </li>
              </ul>
            </section>

            <!-- ═══ RENTAL INFO SECTIONS (Moved Up) ══════════════════════════════ -->

            <!-- Paano Pumunta (How to Get There) -->
            <section v-if="property.direction_steps?.length">
              <h2
                class="theme-title mb-4 flex items-center gap-2 text-lg font-bold"
              >
                <MapIcon class="size-5 text-emerald-600" />
                Paano Pumunta (How to Get There)
              </h2>
              <div class="relative ml-4 border-l-2 border-emerald-200 pl-6">
                <div
                  v-for="(step, i) in property.direction_steps"
                  :key="i"
                  class="relative mb-6 last:mb-0"
                >
                  <!-- Step number dot -->
                  <div
                    class="absolute -left-[33px] flex size-6 items-center justify-center rounded-full bg-emerald-500 text-xs font-bold text-white"
                    style="box-shadow: 0 0 0 4px var(--color-bg)"
                  >
                    {{ i + 1 }}
                  </div>

                  <div class="theme-card rounded-xl p-4 shadow-sm">
                    <!-- Transport mode badge -->
                    <div class="mb-1 flex items-center gap-2">
                      <span
                        v-if="step.transport_mode"
                        class="theme-copy rounded-full px-2 py-0.5 text-xs font-semibold"
                        style="background-color: var(--color-surface-muted)"
                      >
                        {{
                          {
                            walk: "Walk",
                            tricycle: "Tricycle",
                            jeepney: "Jeepney",
                            bus: "Bus",
                            drive: "Drive",
                            grab: "Grab / Taxi",
                          }[step.transport_mode] || step.transport_mode
                        }}
                      </span>
                      <span
                        v-if="step.landmark"
                        class="theme-copy inline-flex items-center gap-1 text-xs"
                      >
                        <MapPinIcon class="size-3.5 shrink-0" />
                        {{ step.landmark }}
                      </span>
                    </div>

                    <p class="theme-title text-sm font-medium">
                      {{ step.instruction }}
                    </p>

                    <!-- Direction photo toggle -->
                    <button
                      v-if="step.photo"
                      type="button"
                      class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-emerald-600 transition hover:text-emerald-700"
                      @click="toggleDirectionPhoto(i)"
                    >
                      <PhotoIcon class="size-4 shrink-0" />
                      {{
                        visibleDirectionPhotos.has(i)
                          ? "Hide photo"
                          : "Show photo"
                      }}
                    </button>

                    <!-- Direction photo -->
                    <div
                      v-if="step.photo && visibleDirectionPhotos.has(i)"
                      class="theme-card mt-2 overflow-hidden rounded-lg shadow-sm"
                    >
                      <img
                        :src="step.photo"
                        :alt="`Direction step ${i + 1}`"
                        class="h-40 w-full object-cover"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </section>
            <!-- ═══ RENTAL INFO SECTIONS ══════════════════════════════ -->
            <!-- Utility Inclusions -->
            <section v-if="property.utility_inclusions?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">
                Utility Inclusions
              </h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="util in property.utility_inclusions"
                  :key="util"
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200/60"
                >
                  <CheckCircleIcon class="size-4" />
                  {{
                    {
                      water: "Water",
                      electricity: "Electricity",
                      wifi: "WiFi / Internet",
                      cable_tv: "Cable TV",
                      gas: "Cooking Gas",
                      trash: "Trash Collection",
                      laundry: "Shared Laundry",
                      parking: "Parking Space",
                    }[util] || util
                  }}
                </span>
              </div>
            </section>

            <!-- House Rules -->
            <section v-if="property.house_rules?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">House Rules</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="rule in property.house_rules"
                  :key="rule"
                  class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 ring-1 ring-amber-200/60"
                >
                  <ExclamationCircleIcon class="size-4" />
                  {{
                    {
                      no_pets: "No Pets",
                      pets_allowed: "Pets Allowed",
                      no_smoking: "No Smoking",
                      no_overnight_guests: "No Overnight Guests",
                      guests_allowed: "Guests Allowed",
                      curfew: "Curfew (10 PM – 6 AM)",
                      no_cooking: "No Cooking in Room",
                      cooking_allowed: "Cooking Allowed",
                      quiet_hours: "Quiet Hours",
                      id_required: "Valid ID Required",
                    }[rule] || rule
                  }}
                </span>
              </div>
            </section>

            <!-- Safety & Security -->
            <section v-if="property.safety_features?.length">
              <h2 class="theme-title mb-3 text-lg font-bold">
                Safety & Security
              </h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="feat in property.safety_features"
                  :key="feat"
                  class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-blue-200/60"
                >
                  <ShieldCheckIcon class="size-4" />
                  {{
                    {
                      cctv: "CCTV",
                      security_guard: "Security Guard",
                      gated: "Gated Compound",
                      well_lit: "Well-Lit",
                      fire_extinguisher: "Fire Extinguisher",
                      smoke_detector: "Smoke Detector",
                      flood_free: "Flood-Free",
                      backup_power: "Backup Power",
                    }[feat] || feat
                  }}
                </span>
              </div>
            </section>

            <!-- ═══ Reviews & Testimonials ═════════════════════════════ -->
            <section class="theme-divider-soft border-t pt-8">
              <ReviewForm
                ref="propertyReviewFormRef"
                :review-count="property.review_count ?? 0"
                :average-rating="property.average_rating ?? null"
                :reviews="property.reviews ?? []"
                :item-label="isRental ? 'rental' : 'property'"
                @submit="submitPropertyReview"
              />
            </section>

            <!-- Share row -->
            <div class="theme-divider-soft flex items-center gap-3 border-t pt-6">
              <span class="theme-copy text-xs font-semibold"
                >Share this listing</span
              >
              <button
                class="btn-secondary flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium shadow-sm transition-colors"
                @click="
                  copyLink();
                  trackEvent('share_click');
                "
              >
                <ShareIcon class="size-3.5" /> Copy link
              </button>
              <a
                :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(route.fullPath)}`"
                target="_blank"
                rel="noopener"
                class="btn-secondary flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium shadow-sm transition-colors hover:text-blue-600"
                @click="trackEvent('share_click')"
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
            <!-- Agent / Landlord Inquiry Card -->
            <div class="theme-card rounded-3xl p-7 shadow-2xl">
              <!-- Agent / Landlord Header -->
              <div
                v-if="property.store"
                class="theme-divider-soft mb-6 border-b pb-6"
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
                      class="theme-copy size-16 rounded-full"
                    />
                    <div
                      class="absolute -bottom-1 -right-1 flex size-5 justify-center items-center rounded-full bg-brand-500 text-white"
                      style="border: 2px solid var(--color-surface)"
                    >
                      <ShieldCheckIcon class="size-3" />
                    </div>
                  </div>
                  <div>
                    <h4 class="theme-title text-lg leading-tight font-bold">
                      {{ property.store.agent_name || property.store.name }}
                    </h4>
                    <p class="theme-copy mt-0.5 text-sm">
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
                        <StarIcon v-else class="theme-copy size-4" />
                      </template>
                      <span class="theme-copy ml-1 text-xs font-medium">
                        {{
                          ratingStars.value
                            ? ratingStars.value.toFixed(1)
                            : "New"
                        }}
                        <span v-if="ratingStars.count" class="theme-copy opacity-80"
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
                  class="btn-secondary mt-5 flex w-full items-center justify-center gap-2 rounded-xl py-3 text-sm font-bold transition-colors"
                  @click="trackEvent('phone_click')"
                >
                  <PhoneIcon class="size-4" />
                  {{ property.store.phone }}
                </a>
              </div>

              <!-- Inquiry Section -->
              <div class="flex flex-col gap-4">
                <!-- Success (shared by both flows) -->
                <div
                  v-if="inquirySuccess"
                  class="flex flex-col items-center gap-2 rounded-2xl bg-[#059669]/10 p-6 text-center ring-1 ring-[#059669]/20"
                >
                  <CheckCircleIcon class="size-8 text-[#059669]" />
                  <p class="theme-title font-bold">
                    Inquiry sent successfully!
                  </p>
                  <p class="theme-copy text-xs">
                    {{ inquirySuccessMessage }}
                  </p>
                  <button
                    class="mt-2 text-xs font-semibold text-[#059669] hover:underline"
                    @click="inquirySuccess = false"
                  >
                    Send another inquiry
                  </button>
                </div>

                <!-- Already Rented State -->
                <div
                  v-if="hasRented"
                  class="flex flex-col items-center gap-3 rounded-2xl bg-emerald-50 p-6 text-center ring-1 ring-emerald-200"
                >
                  <HomeModernIcon class="size-8 text-emerald-600" />
                  <div>
                    <p class="theme-title font-bold">
                      You rent this property!
                    </p>
                    <p class="theme-copy mt-1 text-xs">
                      You have an active or pending rental agreement for this
                      listing.
                    </p>
                  </div>
                  <RouterLink
                    to="/account/rental-agreements"
                    class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                  >
                    View Agreement
                  </RouterLink>
                </div>

                <!-- Unavailable State -->
                <div
                  v-else-if="!property.is_active"
                  class="theme-card-muted flex flex-col items-center gap-3 rounded-2xl p-6 text-center"
                >
                  <LockClosedIcon class="theme-copy size-8" />
                  <div>
                    <p class="theme-title font-bold">No longer available</p>
                    <p class="theme-copy mt-1 text-xs">
                      This property has already been rented or is currently off
                      the market.
                    </p>
                  </div>
                </div>

                <!-- Quick Inquiry (logged-in users) -->
                <template v-else-if="auth.isLoggedIn">
                  <h3 class="theme-title font-bold">Interested?</h3>

                  <!-- Already-inquired label -->
                  <div
                    v-if="hasInquired"
                    class="flex items-center gap-2 rounded-xl bg-amber-50 p-3 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                  >
                    <CheckCircleIcon class="size-4 shrink-0" />
                    You've already sent an inquiry for this listing.
                  </div>

                  <p class="theme-copy text-xs">
                    Express your interest instantly — your contact details will
                    be shared with the
                    {{ isRental ? "landlord" : "agent" }} automatically.
                  </p>

                  <div class="theme-card-muted flex items-center gap-3 rounded-xl p-3">
                    <div
                      class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700"
                    >
                      {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0 text-sm">
                      <p class="theme-title truncate font-medium">
                        {{ auth.user?.name }}
                      </p>
                      <p class="theme-copy truncate text-xs">
                        {{ auth.user?.email }}
                        <span v-if="auth.user?.phone">
                          · {{ auth.user?.phone }}
                        </span>
                      </p>
                    </div>
                  </div>

                  <!-- Auto-generated message preview -->
                  <div
                    v-if="!showQuickMessage"
                    class="theme-card-muted theme-copy rounded-xl px-4 py-3 text-xs italic leading-relaxed"
                  >
                    "{{ defaultMessage }}"
                  </div>

                  <!-- Custom message toggle -->
                  <button
                    v-if="!showQuickMessage"
                    type="button"
                    class="theme-copy flex items-center gap-1.5 self-start text-xs font-medium transition-colors hover:text-[var(--color-text)]"
                    @click="showQuickMessage = true"
                  >
                    <ChatBubbleLeftIcon class="size-3.5" />
                    Write your own message instead
                  </button>
                  <textarea
                    v-if="showQuickMessage"
                    v-model="quickMessage"
                    rows="2"
                    placeholder="I'd like to schedule a viewing…"
                    class="theme-input w-full resize-none rounded-xl px-4 py-3.5 text-sm"
                  />

                  <div
                    v-if="inquiryError"
                    class="flex items-center gap-2 rounded-xl bg-red-50 p-3 text-xs text-red-600 ring-1 ring-red-100"
                  >
                    <ExclamationCircleIcon class="size-4 shrink-0" />
                    {{ inquiryError }}
                  </div>

                  <button
                    type="button"
                    :disabled="inquirySubmitting"
                    class="btn-primary flex w-full items-center justify-center gap-2 rounded-xl py-4 text-sm font-bold transition-all hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                    @click="submitQuickInquiry"
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
                    <template v-else>
                      <HeartIcon class="size-4.5" />
                      I'm Interested
                    </template>
                  </button>

                  <p class="theme-copy flex items-center justify-center gap-1.5 text-[11px] font-medium">
                    <LockClosedIcon class="size-3.5 shrink-0" />
                    Your data is protected and never shared.
                  </p>
                </template>

                <!-- Manual Inquiry Form (guests) -->
                <template v-else>
                  <h3 class="theme-title font-bold">Send an Inquiry</h3>

                  <form
                    class="flex flex-col gap-3"
                    @submit.prevent="submitInquiry"
                  >
                    <input
                      v-model="inquiry.name"
                      required
                      type="text"
                      placeholder="Full Name *"
                      class="theme-input w-full rounded-xl px-4 py-3.5 text-sm"
                    />
                    <input
                      v-model="inquiry.email"
                      required
                      type="email"
                      placeholder="Email Address *"
                      class="theme-input w-full rounded-xl px-4 py-3.5 text-sm"
                    />
                    <input
                      v-model="inquiry.phone"
                      type="tel"
                      placeholder="Phone Number"
                      class="theme-input w-full rounded-xl px-4 py-3.5 text-sm"
                    />
                    <textarea
                      v-model="inquiry.message"
                      rows="3"
                      placeholder="I'm interested in this property. Please contact me for a viewing."
                      class="theme-input w-full resize-none rounded-xl px-4 py-3.5 text-sm"
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

                    <p class="theme-copy mt-2 flex items-center justify-center gap-1.5 text-[11px] font-medium">
                      <LockClosedIcon class="size-3.5 shrink-0" />
                      Your data is protected and never shared.
                    </p>
                  </form>

                  <p class="theme-copy text-center text-xs">
                    <RouterLink
                      to="/login"
                      class="font-semibold text-brand-600 hover:underline"
                    >
                      Log in
                    </RouterLink>
                    to inquire instantly with one click.
                  </p>
                </template>
              </div>
            </div>

            <!-- Share mini card -->
            <div
              class="theme-card rounded-2xl px-5 py-4"
            >
              <p class="theme-copy mb-3 text-xs font-semibold uppercase tracking-wide">
                Share this listing
              </p>
              <div class="flex gap-2">
                <button
                  class="btn-secondary flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold transition-colors"
                  @click="copyLink"
                >
                  <ShareIcon class="size-3.5" /> Copy
                </button>
                <a
                  :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(route.fullPath)}`"
                  target="_blank"
                  rel="noopener"
                  class="btn-secondary flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold transition-colors hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600"
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
                  class="btn-secondary flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold transition-colors"
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

    <!-- Photo Lightbox -->
    <PhotoLightbox
      v-if="lightboxOpen && images.length"
      :images="images"
      :start-index="lightboxIndex"
      :alt="property?.title ?? 'Property photo'"
      @close="lightboxOpen = false"
    />
  </div>
</template>
