<script setup>
import { RouterLink } from "vue-router";
import { TrashIcon, ShoppingBagIcon } from "@heroicons/vue/24/outline";
import { useCartStore } from "@/stores/cart";
import { onMounted } from "vue";
import { useAppI18n } from "@/i18n";

const cart = useCartStore();
const { t } = useAppI18n();
onMounted(() => cart.fetch());
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <div class="mb-8">
      <h1 class="theme-title text-2xl font-bold">{{ t("cart.title") }}</h1>
      <p v-if="cart.lineCount > 0" class="theme-copy mt-1 text-sm">
        {{ cart.totalQuantity }} item{{ cart.totalQuantity !== 1 ? "s" : "" }}
        in your cart
      </p>
    </div>

    <div
      v-if="cart.lineCount === 0"
      class="theme-empty-state rounded-2xl py-20 text-center"
    >
      <div
        class="theme-icon-muted mx-auto mb-4 flex size-14 items-center justify-center rounded-full"
      >
        <ShoppingBagIcon class="size-7" />
      </div>
      <p class="theme-title font-medium">{{ t("cart.empty") }}</p>
      <p class="theme-copy mt-1 text-sm">
        Browse stores and add items to get started.
      </p>
      <RouterLink
        to="/stores"
        class="btn-brand mt-5 inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold transition-all hover:shadow-md"
      >
        {{ t("cart.browse") }} →
      </RouterLink>
    </div>

    <div v-else class="grid gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <ul
          class="theme-card theme-divider-soft divide-y overflow-hidden rounded-2xl"
        >
          <li
            v-for="line in cart.cart?.lines"
            :key="line.id"
            class="flex gap-4 p-4"
          >
            <div
              class="theme-card-muted size-20 shrink-0 overflow-hidden rounded-xl"
            >
              <img
                :src="line.purchasable?.thumbnail ?? '/placeholder.png'"
                class="size-full object-cover"
              />
            </div>
            <div class="flex flex-1 flex-col justify-between min-w-0">
              <p class="theme-title line-clamp-2 font-medium">
                {{ line.purchasable?.name }}
              </p>
              <div class="flex items-center justify-between mt-2">
                <div
                  class="theme-card-muted flex items-center gap-1 rounded-lg p-0.5"
                >
                  <button
                    type="button"
                    class="theme-copy flex size-7 items-center justify-center rounded-md hover:bg-[var(--color-surface)] hover:text-[var(--color-text)] disabled:opacity-40"
                    @click="cart.updateItem(line.id, line.quantity - 1)"
                    :disabled="line.quantity <= 1"
                  >
                    −
                  </button>
                  <span
                    class="theme-title w-6 text-center text-sm font-semibold"
                  >
                    {{ line.quantity }}
                  </span>
                  <button
                    type="button"
                    class="theme-copy flex size-7 items-center justify-center rounded-md hover:bg-[var(--color-surface)] hover:text-[var(--color-text)]"
                    @click="cart.updateItem(line.id, line.quantity + 1)"
                  >
                    +
                  </button>
                </div>
                <div class="flex items-center gap-3">
                  <span class="theme-title font-bold">{{
                    line.sub_total?.formatted
                  }}</span>
                  <button
                    type="button"
                    class="theme-copy rounded-lg p-1.5 hover:bg-red-500/10 hover:text-red-500"
                    @click="cart.removeItem(line.id)"
                  >
                    <TrashIcon class="size-4" />
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <div class="lg:col-span-1">
        <div
          class="theme-card sticky top-24 rounded-2xl p-5"
        >
          <h2 class="theme-title mb-4 text-base font-semibold">
            {{ t("cart.orderSummary") }}
          </h2>
          <div class="theme-copy space-y-2 text-sm">
            <div class="flex justify-between">
              <span>{{ t("cart.subtotal") }}</span>
              <span class="theme-title font-medium">{{ cart.originalTotal }}</span>
            </div>
            <div
              v-if="cart.appliedCoupon"
              class="flex justify-between text-emerald-500"
            >
              <span>{{ cart.appliedCoupon.code }}</span>
              <span>-{{ cart.discountTotal }}</span>
            </div>
            <div class="flex justify-between">
              <span>{{ t("cart.delivery") }}</span>
              <span>Calculated at checkout</span>
            </div>
          </div>
          <div class="theme-divider-soft my-4 border-t" />
          <div class="theme-title flex justify-between text-base font-bold">
            <span>{{ t("cart.total") }}</span>
            <span>{{ cart.total }}</span>
          </div>
          <RouterLink
            to="/checkout"
            class="btn-brand mt-4 flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-bold shadow-sm hover:shadow-md active:scale-[0.98]"
          >
            {{ t("cart.proceed") }}
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
          <p class="theme-copy mt-3 text-center text-xs">
            Taxes and delivery calculated at checkout
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
