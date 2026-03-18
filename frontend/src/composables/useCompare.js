import { ref, computed } from "vue";

const MAX_COMPARE = 3;
const STORAGE_KEY = "compare_ids";

function loadFromStorage() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? "[]");
  } catch {
    return [];
  }
}

// Module-level reactive state so it's shared across components
const compareList = ref(loadFromStorage());

function persist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(compareList.value));
}

export function useCompare() {
  const canAdd = computed(() => compareList.value.length < MAX_COMPARE);

  function isInCompare(id) {
    return compareList.value.some((p) => p.id === id);
  }

  function addToCompare(property) {
    if (isInCompare(property.id) || !canAdd.value) return;
    compareList.value.push({
      id: property.id,
      slug: property.slug,
      title: property.title,
      price: property.formatted_price,
      city: property.city,
      image: property.images?.[0] ?? null,
    });
    persist();
  }

  function removeFromCompare(id) {
    compareList.value = compareList.value.filter((p) => p.id !== id);
    persist();
  }

  function clearCompare() {
    compareList.value = [];
    persist();
  }

  return {
    compareList,
    canAdd,
    isInCompare,
    addToCompare,
    removeFromCompare,
    clearCompare,
  };
}
