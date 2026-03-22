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
      class="theme-overlay fixed inset-0 z-50"
      @click="$emit('close')"
    />
  </Transition>

  <!-- Drawer -->
  <Transition name="slide">
    <aside
      v-if="open"
      class="theme-modal fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col border-y-0 border-r-0"
    >
      <!-- Header -->
      <div
        class="theme-divider flex items-center justify-between border-b px-5 py-4"
      >
        <div class="flex items-center gap-2">
          <ShoppingBagIcon class="size-5" style="color: var(--color-text-muted)" />
          <h2 class="text-base font-semibold" style="color: var(--color-text)">
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
          class="rounded-lg p-1.5 transition-colors hover:bg-[var(--color-surface-muted)]"
          style="color: var(--color-text-muted)"
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
          class="flex size-16 items-center justify-center rounded-full"
          style="background-color: var(--color-surface-muted)"
        >
          <ShoppingBagIcon class="size-8" style="color: var(--color-text-muted)" />
        </div>
        <div>
          <p class="font-medium" style="color: var(--color-text)">Your cart is empty</p>
          <p class="mt-1 text-sm" style="color: var(--color-text-muted)">
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
          <p style="color: var(--color-text-muted)">
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
        class="flex-1 overflow-y-auto px-1"
      >
        <li
          v-for="line in cart.cart?.lines"
          :key="line.id"
          class="flex gap-3 border-b px-4 py-3.5 last:border-b-0"
          style="border-color: var(--color-border)"
        >
          <div
            class="size-16 shrink-0 overflow-hidden rounded-xl"
            style="background-color: var(--color-surface-muted)"
          >
            <img
              :src="line.purchasable?.thumbnail ?? '/placeholder.png'"
              :alt="line.purchasable?.name"
              class="size-full object-cover"
            />
          </div>
          <div class="flex flex-1 flex-col justify-between min-w-0">
            <p
              class="line-clamp-2 text-sm font-medium leading-snug"
              style="color: var(--color-text)"
            >
              {{ line.purchasable?.name }}
            </p>
            <div class="flex items-center justify-between mt-2">
              <!-- Quantity controls -->
              <div
                class="flex items-center gap-1 rounded-lg p-0.5"
                style="
                  border: 1px solid var(--color-border);
                  background-color: var(--color-surface-muted);
                "
              >
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md transition-colors hover:bg-[var(--color-surface)] disabled:opacity-40"
                  style="color: var(--color-text-muted)"
                  @click="cart.updateItem(line.id, line.quantity - 1)"
                  :disabled="line.quantity <= 1"
                >
                  −
                </button>
                <span
                  class="w-5 text-center text-xs font-semibold"
                  style="color: var(--color-text)"
                >
                  {{ line.quantity }}
                </span>
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md transition-colors hover:bg-[var(--color-surface)]"
                  style="color: var(--color-text-muted)"
                  @click="cart.updateItem(line.id, line.quantity + 1)"
                >
                  +
                </button>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold" style="color: var(--color-text)">{{
                  line.sub_total?.formatted
                }}</span>
                <button
                  type="button"
                  class="rounded-md p-1 transition-colors hover:bg-red-50 hover:text-red-500"
                  style="color: var(--color-text-muted)"
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
        class="theme-divider space-y-3 border-t px-5 py-4"
        style="background-color: color-mix(in srgb, var(--color-surface-muted) 76%, transparent)"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm" style="color: var(--color-text-muted)">Subtotal</span>
          <span class="text-base font-bold" style="color: var(--color-text)">{{
            cart.total
          }}</span>
        </div>
        <RouterLink
          to="/checkout"
          class="btn-brand flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-bold shadow-sm active:scale-[0.98] transition-all hover:shadow-brand-500/25 hover:shadow-md"
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
        <p class="text-center text-xs" style="color: var(--color-text-muted)">
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
