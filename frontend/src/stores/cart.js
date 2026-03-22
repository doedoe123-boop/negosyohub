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
  const originalTotal = computed(
    () =>
      cart.value?.original_total?.formatted ??
      cart.value?.total?.formatted ??
      "₱0.00",
  );
  const discountTotal = computed(
    () => cart.value?.discount_total?.formatted ?? "₱0.00",
  );
  const appliedCoupon = computed(() => cart.value?.applied_coupon ?? null);
  const rawTotal = computed(() => {
    const val = cart.value?.total?.value;
    if (val == null) return 0;
    // Lunar stores amounts in minor units (centavos)
    return val / 100;
  });
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

  async function applyCoupon(code) {
    const { data } = await cartApi.applyCoupon(code);
    cart.value = data;
  }

  async function removeCoupon() {
    const { data } = await cartApi.removeCoupon();
    cart.value = data;
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
  function reset() {
    cart.value = null;
    isOpen.value = false;
  }

  return {
    cart,
    isOpen,
    loading,
    lineCount,
    totalQuantity,
    total,
    originalTotal,
    discountTotal,
    appliedCoupon,
    rawTotal,
    storeId,
    fetch,
    addItem,
    updateItem,
    removeItem,
    clear,
    applyCoupon,
    removeCoupon,
    openDrawer,
    closeDrawer,
    toggleDrawer,
    reset,
  };
});
