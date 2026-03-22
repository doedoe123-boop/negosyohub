<script setup>
import { ref, computed } from "vue";
import { StarIcon } from "@heroicons/vue/24/outline";
import { StarIcon as StarSolid } from "@heroicons/vue/24/solid";
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
} from "@heroicons/vue/24/outline";
import { useAuthStore } from "@/stores/auth";

const props = defineProps({
  /** Total existing review count shown in the section header */
  reviewCount: { type: Number, default: 0 },
  /** Average rating shown in the section header */
  averageRating: { type: Number, default: null },
  /** Existing reviews to display */
  reviews: { type: Array, default: () => [] },
  /** Label: "product" or "property" */
  itemLabel: { type: String, default: "item" },
});

const emit = defineEmits(["submit"]);

const auth = useAuthStore();

const rating = ref(0);
const hoverRating = ref(0);
const title = ref("");
const content = ref("");
const submitting = ref(false);
const success = ref(false);
const error = ref(null);

const displayRating = computed(() => hoverRating.value || rating.value);

function setRating(star) {
  rating.value = star;
}

function resetForm() {
  rating.value = 0;
  hoverRating.value = 0;
  title.value = "";
  content.value = "";
  error.value = null;
}

async function handleSubmit() {
  if (!rating.value) {
    error.value = "Please select a star rating.";
    return;
  }
  if (content.value.trim().length < 10) {
    error.value = "Your review must be at least 10 characters.";
    return;
  }

  submitting.value = true;
  error.value = null;

  try {
    emit("submit", {
      rating: rating.value,
      title: title.value.trim() || null,
      content: content.value.trim(),
    });
  } catch {
    // handled by parent
  }
}

/** Called by parent after successful submission */
function onSuccess() {
  submitting.value = false;
  success.value = true;
  resetForm();
}

/** Called by parent on error */
function onError(msg) {
  submitting.value = false;
  error.value = msg;
}

defineExpose({ onSuccess, onError });
</script>

<template>
  <section>
    <!-- Header with average rating summary -->
    <div
      class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <h2 class="text-xl font-bold text-[#0F2044] dark:text-white">
        Reviews
        <span v-if="reviewCount" class="text-base font-normal text-slate-400">
          ({{ reviewCount }})
        </span>
      </h2>

      <div
        v-if="averageRating"
        class="flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-2.5 ring-1 ring-amber-100"
      >
        <span class="text-2xl font-black text-[#0F2044]">{{
          averageRating.toFixed(1)
        }}</span>
        <div>
          <div class="flex gap-0.5">
            <template v-for="n in 5" :key="n">
              <StarSolid
                v-if="n <= Math.round(averageRating)"
                class="size-4 text-amber-400"
              />
              <StarIcon v-else class="size-4 text-slate-200" />
            </template>
          </div>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ reviewCount }} {{ reviewCount === 1 ? "review" : "reviews" }}
          </p>
        </div>
      </div>
    </div>

    <!-- Existing reviews list -->
    <div v-if="reviews.length" class="mb-8 space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm dark:bg-slate-800 dark:border-slate-700"
      >
        <div class="mb-2 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="flex gap-0.5">
              <StarSolid
                v-for="n in review.rating"
                :key="'f-' + n"
                class="size-3.5 text-amber-400"
              />
              <StarIcon
                v-for="n in 5 - review.rating"
                :key="'e-' + n"
                class="size-3.5 text-slate-200"
              />
            </div>
            <span class="text-sm font-bold text-[#0F2044]">{{
              review.name
            }}</span>
            <span
              v-if="review.verified"
              class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-600 ring-1 ring-emerald-200"
            >
              Verified
            </span>
          </div>
          <span class="text-xs text-slate-400">{{ review.date }}</span>
        </div>
        <p
          v-if="review.title"
          class="mb-1 text-sm font-semibold text-slate-800"
        >
          {{ review.title }}
        </p>
        <p class="text-sm leading-relaxed text-slate-600">
          {{ review.content }}
        </p>
      </div>
    </div>

    <div
      v-else
      class="mb-8 rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center dark:bg-slate-800/50 dark:border-slate-700"
    >
      <p class="text-sm font-medium text-slate-500">
        No reviews yet. Be the first to share your experience!
      </p>
    </div>

    <!-- Review form -->
    <div
      class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:bg-slate-800 dark:border-slate-700"
    >
      <h3 class="mb-5 text-base font-bold text-[#0F2044] dark:text-white">Write a Review</h3>

      <!-- Not logged in -->
      <div
        v-if="!auth.isLoggedIn"
        class="rounded-xl bg-slate-50 p-5 text-center"
      >
        <p class="text-sm text-slate-600">
          Please
          <RouterLink
            to="/login"
            class="font-semibold text-brand-600 hover:underline"
            >log in</RouterLink
          >
          to write a review.
        </p>
      </div>

      <!-- Success state -->
      <div
        v-else-if="success"
        class="flex flex-col items-center gap-3 rounded-xl bg-emerald-50 p-6 text-center ring-1 ring-emerald-100"
      >
        <CheckCircleIcon class="size-10 text-emerald-500" />
        <p class="font-bold text-[#0F2044]">Thank you for your review!</p>
        <p class="text-sm text-slate-500">
          Your review has been submitted and is pending approval.
        </p>
        <button
          class="mt-2 text-sm font-semibold text-emerald-600 hover:underline"
          @click="success = false"
        >
          Write another review
        </button>
      </div>

      <!-- Form -->
      <form v-else class="space-y-5" @submit.prevent="handleSubmit">
        <!-- Star rating -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300"
            >Rating</label
          >
          <div class="flex gap-1">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              class="transition-transform hover:scale-110 focus:outline-none"
              @click="setRating(star)"
              @mouseenter="hoverRating = star"
              @mouseleave="hoverRating = 0"
            >
              <StarSolid
                v-if="star <= displayRating"
                class="size-8 text-amber-400 drop-shadow-sm"
              />
              <StarIcon v-else class="size-8 text-slate-200" />
            </button>
          </div>
          <p v-if="displayRating" class="mt-1 text-xs text-slate-400">
            {{
              ["", "Poor", "Fair", "Good", "Very Good", "Excellent"][
                displayRating
              ]
            }}
          </p>
        </div>

        <!-- Title-->
        <div class="relative">
          <input
            v-model="title"
            type="text"
            placeholder=" "
            maxlength="255"
            class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 dark:text-white transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
          />
          <label
            class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white dark:bg-slate-700 px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
          >
            Review Title
          </label>
        </div>

        <!-- Content -->
        <div>
          <textarea
            v-model="content"
            rows="4"
            :placeholder="`Share your experience with this ${itemLabel}...`"
            maxlength="2000"
            class="w-full resize-none rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
          />
          <p class="mt-1 text-right text-xs text-slate-400">
            {{ content.length }} / 2000
          </p>
        </div>

        <!-- Error -->
        <div
          v-if="error"
          class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 ring-1 ring-red-100"
        >
          <ExclamationCircleIcon class="size-4 shrink-0" />
          {{ error }}
        </div>

        <!-- Submit button -->
        <button
          type="submit"
          :disabled="submitting || !rating || content.trim().length < 10"
          class="w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-bold text-white shadow-sm transition-all hover:from-brand-600 hover:to-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ submitting ? "Submitting…" : "Submit Review" }}
        </button>
      </form>
    </div>
  </section>
</template>
