<script setup>
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
import { MapPinIcon, XMarkIcon } from "@heroicons/vue/20/solid";
import { useCityStore } from "@/stores/city";

const city = useCityStore();
const route = useRoute();

/** Only show the banner on listing pages. */
const listingPaths = [
  "/stores",
  "/products",
  "/properties",
  "/developments",
  "/movers",
];
const isListingPage = () => listingPaths.some((p) => route.path.startsWith(p));

const showBanner = ref(false);
const editMode = ref(false);
const inputValue = ref("");

watch(
  () => [city.activeCity, route.path],
  () => {
    showBanner.value = !!city.activeCity && isListingPage();
  },
  { immediate: true },
);

function openEdit() {
  inputValue.value = city.selectedCity || "";
  editMode.value = true;
}

function applyCity() {
  const trimmed = inputValue.value.trim();
  if (trimmed) {
    city.setCity(trimmed);
  }
  editMode.value = false;
}

function browseAll() {
  city.clearAll();
  editMode.value = false;
}

function onInputKeydown(e) {
  if (e.key === "Enter") {
    applyCity();
  }
  if (e.key === "Escape") {
    editMode.value = false;
  }
}
</script>

<template>
  <div
    v-if="showBanner"
    class="border-b border-emerald-100 bg-emerald-50 px-4 py-2"
  >
    <div
      class="mx-auto flex max-w-7xl items-center gap-2 text-sm text-emerald-800"
    >
      <MapPinIcon class="size-4 shrink-0 text-emerald-600" />

      <template v-if="!editMode">
        <span>
          Browsing listings in
          <strong class="font-semibold">{{ city.activeCity }}</strong>
        </span>
        <button
          class="ml-1 font-medium underline underline-offset-2 hover:text-emerald-700"
          @click="openEdit"
        >
          Change city
        </button>
        <span class="text-emerald-300">·</span>
        <button
          class="font-medium underline underline-offset-2 hover:text-emerald-700"
          @click="browseAll"
        >
          Browse all
        </button>
      </template>

      <template v-else>
        <label class="sr-only" for="city-input">Enter a city</label>
        <input
          id="city-input"
          v-model="inputValue"
          type="text"
          placeholder="e.g. Cebu City"
          class="ml-1 w-40 rounded-md border border-emerald-300 px-2 py-0.5 text-sm outline-none focus:ring-2 focus:ring-emerald-400"
          style="
            background-color: var(--color-surface);
            color: var(--color-text);
          "
          @keydown="onInputKeydown"
          autofocus
        />
        <button
          class="ml-1 rounded-md bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white hover:bg-emerald-700"
          @click="applyCity"
        >
          Apply
        </button>
        <button
          class="ml-1 text-emerald-500 hover:text-emerald-700"
          @click="editMode = false"
        >
          <XMarkIcon class="size-4" />
          <span class="sr-only">Cancel</span>
        </button>
      </template>
    </div>
  </div>
</template>
