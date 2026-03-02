import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { cartApi } from "@/api/cart";

export const useCartStore = defineStore("cart", () => {
  const cart = ref(null);
  const isOpen = ref(false);
  const loading = ref(false);

  const lineCount = computed(() => cart.value?.lines?.length ?? 0);
  const totalQuantity = computed(
    () => cart.value?.lines?.reduce((sum, line) => sum + line.quantity, 0) ?? 0,
  );
  const total = computed(() => cart.value?.total?.formatted ?? "₱0.00");
  const storeId = computed(() => cart.value?.meta?.store_id ?? null);

  async function fetch() {
    try {
      const { data } = await cartApi.get();
      cart.value = data;
    } catch {
      cart.value = null;
    }
  }

  async function addItem(
    purchasableType,
    purchasableId,
    quantity = 1,
    meta = {},
  ) {
    loading.value = true;
    try {
      const { data } = await cartApi.addItem(
        purchasableType,
        purchasableId,
        quantity,
        meta,
      );
      cart.value = data;
      isOpen.value = true;
    } finally {
      loading.value = false;
    }
  }

  async function updateItem(lineId, quantity) {
    loading.value = true;
    try {
      const { data } = await cartApi.updateItem(lineId, quantity);
      cart.value = data;
    } finally {
      loading.value = false;
    }
  }

  async function removeItem(lineId) {
    loading.value = true;
    try {
      const { data } = await cartApi.removeItem(lineId);
      cart.value = data;
    } finally {
      loading.value = false;
    }
  }

  async function clear() {
    await cartApi.clear();
    cart.value = null;
  }

  function openDrawer() {
    isOpen.value = true;
  }
  function closeDrawer() {
    isOpen.value = false;
  }
  function toggleDrawer() {
    isOpen.value = !isOpen.value;
  }

  return {
    cart,
    isOpen,
    loading,
    lineCount,
    totalQuantity,
    total,
    storeId,
    fetch,
    addItem,
    updateItem,
    removeItem,
    clear,
    openDrawer,
    closeDrawer,
    toggleDrawer,
  };
});
