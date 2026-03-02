<script setup>
import { RouterLink } from "vue-router";
import {
  ShoppingCartIcon,
  Bars3Icon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";

const auth = useAuthStore();
const cart = useCartStore();
const mobileOpen = ref(false);
</script>

<template>
  <header class="sticky top-0 z-40 border-b bg-white shadow-sm">
    <div
      class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6"
    >
      <!-- Logo -->
      <RouterLink to="/" class="text-xl font-bold text-brand-600">
        NegosyoHub
      </RouterLink>

      <!-- Desktop nav -->
      <nav
        class="hidden items-center gap-6 text-sm font-medium text-gray-600 md:flex"
      >
        <RouterLink to="/stores" class="hover:text-brand-600 transition-colors"
          >Stores</RouterLink
        >
        <RouterLink
          to="/properties"
          class="hover:text-brand-600 transition-colors"
          >Properties</RouterLink
        >
      </nav>

      <!-- Right actions -->
      <div class="flex items-center gap-3">
        <!-- Cart button -->
        <button
          type="button"
          class="relative rounded-full p-2 text-gray-600 hover:bg-gray-100"
          @click="cart.toggleDrawer"
        >
          <ShoppingCartIcon class="size-6" />
          <span
            v-if="cart.totalQuantity > 0"
            class="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white"
          >
            {{ cart.totalQuantity }}
          </span>
        </button>

        <!-- Auth links -->
        <template v-if="!auth.isLoggedIn">
          <RouterLink
            to="/login"
            class="text-sm font-medium text-gray-700 hover:text-brand-600"
          >
            Sign in
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition-colors"
          >
            Register
          </RouterLink>
        </template>

        <template v-else>
          <RouterLink
            to="/account/orders"
            class="text-sm font-medium text-gray-700 hover:text-brand-600"
          >
            My Orders
          </RouterLink>
          <button
            type="button"
            class="text-sm font-medium text-gray-500 hover:text-red-500"
            @click="auth.logout"
          >
            Sign out
          </button>
        </template>

        <!-- Mobile menu toggle -->
        <button
          type="button"
          class="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden"
          @click="mobileOpen = !mobileOpen"
        >
          <XMarkIcon v-if="mobileOpen" class="size-6" />
          <Bars3Icon v-else class="size-6" />
        </button>
      </div>
    </div>

    <!-- Mobile nav -->
    <nav v-if="mobileOpen" class="border-t px-4 py-3 md:hidden">
      <RouterLink
        to="/stores"
        class="block py-2 text-sm font-medium text-gray-700"
        @click="mobileOpen = false"
        >Stores</RouterLink
      >
      <RouterLink
        to="/properties"
        class="block py-2 text-sm font-medium text-gray-700"
        @click="mobileOpen = false"
        >Properties</RouterLink
      >
    </nav>
  </header>
</template>
