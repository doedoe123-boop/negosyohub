<script setup>
import { RouterLink } from "vue-router";
import { TrashIcon } from "@heroicons/vue/24/outline";
import { useCartStore } from "@/stores/cart";
import { onMounted } from "vue";

const cart = useCartStore();
onMounted(() => cart.fetch());
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold text-gray-900">Your Cart</h1>

    <div v-if="cart.lineCount === 0" class="py-16 text-center">
      <p class="text-gray-400">Your cart is empty.</p>
      <RouterLink
        to="/stores"
        class="mt-4 inline-block text-sm font-medium text-brand-600 hover:underline"
      >
        Browse stores →
      </RouterLink>
    </div>

    <div v-else>
      <ul class="divide-y rounded-2xl border bg-white">
        <li
          v-for="line in cart.cart?.lines"
          :key="line.id"
          class="flex gap-4 p-4"
        >
          <img
            :src="line.purchasable?.thumbnail ?? '/placeholder.png'"
            class="size-20 rounded-xl object-cover bg-gray-100"
          />
          <div class="flex flex-1 flex-col justify-between">
            <p class="font-medium text-gray-800">
              {{ line.purchasable?.name }}
            </p>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 text-sm">
                <button
                  type="button"
                  class="size-7 rounded border hover:bg-gray-100"
                  @click="cart.updateItem(line.id, line.quantity - 1)"
                  :disabled="line.quantity <= 1"
                >
                  −
                </button>
                <span>{{ line.quantity }}</span>
                <button
                  type="button"
                  class="size-7 rounded border hover:bg-gray-100"
                  @click="cart.updateItem(line.id, line.quantity + 1)"
                >
                  +
                </button>
              </div>
              <div class="flex items-center gap-3">
                <span class="font-semibold text-gray-900">{{
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

      <div class="mt-6 rounded-2xl border bg-white p-5">
        <div class="flex justify-between text-base font-semibold text-gray-900">
          <span>Total</span>
          <span>{{ cart.total }}</span>
        </div>
        <RouterLink
          to="/checkout"
          class="mt-4 block w-full rounded-xl bg-brand-500 py-3 text-center text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
        >
          Proceed to Checkout
        </RouterLink>
      </div>
    </div>
  </div>
</template>
