<script setup>
import { RouterLink } from "vue-router";
import {
  XMarkIcon,
  TrashIcon,
  ShoppingBagIcon,
} from "@heroicons/vue/24/outline";
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
      class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm"
      @click="$emit('close')"
    />
  </Transition>

  <!-- Drawer -->
  <Transition name="slide">
    <aside
      v-if="open"
      class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-white shadow-2xl"
    >
      <!-- Header -->
      <div
        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
      >
        <div class="flex items-center gap-2">
          <ShoppingBagIcon class="size-5 text-slate-600" />
          <h2 class="text-base font-semibold text-slate-900">
            Your Cart
            <span
              v-if="cart.lineCount > 0"
              class="ml-1.5 rounded-full bg-brand-100 px-1.5 py-0.5 text-xs font-bold text-brand-600"
            >
              {{ cart.lineCount }}
            </span>
          </h2>
        </div>
        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
          @click="$emit('close')"
        >
          <XMarkIcon class="size-4.5" />
        </button>
      </div>

      <!-- Empty state -->
      <div
        v-if="cart.lineCount === 0"
        class="flex flex-1 flex-col items-center justify-center gap-4 px-6 text-center"
      >
        <div
          class="flex size-16 items-center justify-center rounded-full bg-slate-100"
        >
          <ShoppingBagIcon class="size-8 text-slate-400" />
        </div>
        <div>
          <p class="font-medium text-slate-700">Your cart is empty</p>
          <p class="mt-1 text-sm text-slate-400">
            Add items from a store to get started.
          </p>
        </div>
        <RouterLink
          to="/stores"
          class="mt-1 inline-flex items-center gap-1.5 rounded-xl bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-100 transition-colors"
          @click="$emit('close')"
        >
          Browse stores →
        </RouterLink>
      </div>

      <!-- Free shipping progress -->
      <div
        v-if="cart.lineCount > 0"
        class="mx-4 mb-1 mt-3 rounded-xl bg-brand-50 px-4 py-3 text-sm"
      >
        <template v-if="cart.rawTotal >= 500">
          <p class="font-semibold text-brand-700">
            🎉 You qualify for free shipping!
          </p>
        </template>
        <template v-else>
          <p class="text-slate-600">
            Add
            <span class="font-bold text-brand-600">
              ₱{{
                (500 - cart.rawTotal).toLocaleString("en-PH", {
                  maximumFractionDigits: 0,
                })
              }}
            </span>
            more for free shipping
          </p>
          <div
            class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-brand-100"
          >
            <div
              class="h-full rounded-full bg-brand-500 transition-all duration-500"
              :style="{
                width: Math.min((cart.rawTotal / 500) * 100, 100) + '%',
              }"
            />
          </div>
        </template>
      </div>

      <!-- Lines -->
      <ul
        v-if="cart.lineCount > 0"
        class="flex-1 divide-y divide-slate-100 overflow-y-auto px-1"
      >
        <li
          v-for="line in cart.cart?.lines"
          :key="line.id"
          class="flex gap-3 px-4 py-3.5"
        >
          <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
            <img
              :src="line.purchasable?.thumbnail ?? '/placeholder.png'"
              :alt="line.purchasable?.name"
              class="size-full object-cover"
            />
          </div>
          <div class="flex flex-1 flex-col justify-between min-w-0">
            <p
              class="text-sm font-medium text-slate-800 line-clamp-2 leading-snug"
            >
              {{ line.purchasable?.name }}
            </p>
            <div class="flex items-center justify-between mt-2">
              <!-- Quantity controls -->
              <div
                class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-0.5"
              >
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md text-slate-600 hover:bg-white hover:text-slate-900 disabled:opacity-40 transition-colors"
                  @click="cart.updateItem(line.id, line.quantity - 1)"
                  :disabled="line.quantity <= 1"
                >
                  −
                </button>
                <span
                  class="w-5 text-center text-xs font-semibold text-slate-700"
                >
                  {{ line.quantity }}
                </span>
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md text-slate-600 hover:bg-white hover:text-slate-900 transition-colors"
                  @click="cart.updateItem(line.id, line.quantity + 1)"
                >
                  +
                </button>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900">{{
                  line.sub_total?.formatted
                }}</span>
                <button
                  type="button"
                  class="rounded-md p-1 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                  @click="cart.removeItem(line.id)"
                >
                  <TrashIcon class="size-3.5" />
                </button>
              </div>
            </div>
          </div>
        </li>
      </ul>

      <!-- Footer -->
      <div
        v-if="cart.lineCount > 0"
        class="border-t border-slate-100 px-5 py-4 space-y-3 bg-slate-50/60"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm text-slate-600">Subtotal</span>
          <span class="text-base font-bold text-slate-900">{{
            cart.total
          }}</span>
        </div>
        <RouterLink
          to="/checkout"
          class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-bold text-white shadow-sm hover:from-brand-600 hover:to-brand-700 hover:shadow-brand-500/25 hover:shadow-md active:scale-[0.98] transition-all"
          @click="$emit('close')"
        >
          Proceed to Checkout
          <svg
            class="size-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"
            />
          </svg>
        </RouterLink>
        <p class="text-center text-xs text-slate-400">
          Taxes and delivery calculated at checkout
        </p>
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
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>
