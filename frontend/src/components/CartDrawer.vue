<script setup>
import { RouterLink } from "vue-router";
import { XMarkIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { useCartStore } from "@/stores/cart";

defineProps({ open: Boolean });
defineEmits(["close"]);

const cart = useCartStore();
</script>

<template>
  <!-- Backdrop -->
  <Transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-50 bg-black/40"
      @click="$emit('close')"
    />
  </Transition>

  <!-- Drawer -->
  <Transition name="slide">
    <aside
      v-if="open"
      class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-white shadow-xl"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b px-4 py-4">
        <h2 class="text-lg font-semibold text-gray-900">Your Cart</h2>
        <button
          type="button"
          class="rounded-md p-1 text-gray-500 hover:text-gray-700"
          @click="$emit('close')"
        >
          <XMarkIcon class="size-5" />
        </button>
      </div>

      <!-- Empty state -->
      <div
        v-if="cart.lineCount === 0"
        class="flex flex-1 flex-col items-center justify-center gap-3 text-gray-400"
      >
        <p class="text-sm">Your cart is empty.</p>
        <RouterLink
          to="/stores"
          class="text-sm font-medium text-brand-600 hover:underline"
          @click="$emit('close')"
        >
          Browse stores →
        </RouterLink>
      </div>

      <!-- Lines -->
      <ul v-else class="flex-1 divide-y overflow-y-auto">
        <li
          v-for="line in cart.cart?.lines"
          :key="line.id"
          class="flex gap-3 px-4 py-3"
        >
          <img
            :src="line.purchasable?.thumbnail ?? '/placeholder.png'"
            :alt="line.purchasable?.name"
            class="size-16 rounded-lg object-cover bg-gray-100"
          />
          <div class="flex flex-1 flex-col justify-between">
            <p class="text-sm font-medium text-gray-800 line-clamp-2">
              {{ line.purchasable?.name }}
            </p>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 text-sm">
                <button
                  type="button"
                  class="size-6 rounded border text-center text-gray-600 hover:bg-gray-100"
                  @click="cart.updateItem(line.id, line.quantity - 1)"
                  :disabled="line.quantity <= 1"
                >
                  −
                </button>
                <span>{{ line.quantity }}</span>
                <button
                  type="button"
                  class="size-6 rounded border text-center text-gray-600 hover:bg-gray-100"
                  @click="cart.updateItem(line.id, line.quantity + 1)"
                >
                  +
                </button>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-900">{{
                  line.sub_total?.formatted
                }}</span>
                <button
                  type="button"
                  class="text-gray-400 hover:text-red-500"
                  @click="cart.removeItem(line.id)"
                >
                  <TrashIcon class="size-4" />
                </button>
              </div>
            </div>
          </div>
        </li>
      </ul>

      <!-- Footer -->
      <div v-if="cart.lineCount > 0" class="border-t px-4 py-4 space-y-3">
        <div class="flex justify-between text-sm font-semibold text-gray-900">
          <span>Total</span>
          <span>{{ cart.total }}</span>
        </div>
        <RouterLink
          to="/checkout"
          class="block w-full rounded-xl bg-brand-500 py-3 text-center text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
          @click="$emit('close')"
        >
          Proceed to Checkout
        </RouterLink>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>
