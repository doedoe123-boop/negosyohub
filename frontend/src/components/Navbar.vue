<script setup>
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  ShoppingCartIcon,
  Bars3Icon,
  XMarkIcon,
  UserCircleIcon,
  MagnifyingGlassIcon,
} from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";

const auth = useAuthStore();
const cart = useCartStore();
const route = useRoute();
const router = useRouter();

async function handleLogout() {
  await auth.logout();
  router.push("/");
}
const mobileOpen = ref(false);
const storesFlyout = ref(false);

const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";

const sectors = [
  { label: "Stores", to: "/stores" },
  { label: "Properties", to: "/properties" },
];

const storeSectors = [
  { label: "E-Commerce", value: "ecommerce", icon: "🛍️" },
  { label: "Real Estate", value: "real_estate", icon: "🏠" },
  { label: "Services", value: "services", icon: "🔧" },
];

function isActive(path) {
  return route.path.startsWith(path);
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-navy-900 shadow-md">
    <div
      class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6"
    >
      <!-- Logo -->
      <RouterLink to="/" class="flex shrink-0 items-center gap-2.5 group">
        <span
          class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700 text-sm font-bold text-white shadow-sm ring-1 ring-emerald-600/30 transition-shadow group-hover:shadow-emerald-500/40 group-hover:shadow-md"
        >
          N
        </span>
        <span
          class="hidden text-[1.05rem] font-bold tracking-tight text-white sm:block"
        >
          Negosyo<span class="text-emerald-400">Hub</span>
        </span>
      </RouterLink>

      <!-- Desktop nav -->
      <nav class="hidden flex-1 items-center gap-0.5 md:flex">
        <!-- Stores with flyout -->
        <div
          class="relative"
          @mouseenter="storesFlyout = true"
          @mouseleave="storesFlyout = false"
        >
          <RouterLink
            to="/stores"
            class="relative flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
            :class="
              isActive('/stores')
                ? 'bg-white/10 text-white'
                : 'text-white/70 hover:bg-white/10 hover:text-white'
            "
          >
            Stores
            <svg
              class="size-3.5 transition-transform"
              :class="storesFlyout ? 'rotate-180' : ''"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m19 9-7 7-7-7"
              />
            </svg>
            <span
              v-if="isActive('/stores')"
              class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-emerald-400"
            />
          </RouterLink>

          <!-- Flyout -->
          <Transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-1 opacity-0"
          >
            <div v-if="storesFlyout" class="absolute left-0 top-full z-50 pt-1">
              <div
                class="w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-xl ring-1 ring-black/5"
              >
                <RouterLink
                  v-for="s in storeSectors"
                  :key="s.value"
                  :to="`/stores?sector=${s.value}`"
                  class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                  @click="storesFlyout = false"
                >
                  <span class="text-base">{{ s.icon }}</span>
                  {{ s.label }}
                </RouterLink>
                <div class="my-1.5 border-t border-slate-100" />
                <RouterLink
                  to="/stores"
                  class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700"
                  @click="storesFlyout = false"
                >
                  <span class="text-base">🔍</span>
                  Browse all stores
                </RouterLink>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Properties -->
        <RouterLink
          to="/properties"
          class="relative rounded-lg px-4 py-2 text-sm font-medium transition-colors"
          :class="
            isActive('/properties')
              ? 'bg-white/10 text-white'
              : 'text-white/70 hover:bg-white/10 hover:text-white'
          "
        >
          Properties
          <span
            v-if="isActive('/properties')"
            class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-emerald-400"
          />
        </RouterLink>

        <span class="mx-1 h-4 w-px bg-white/20" />

        <a
          :href="`${backendUrl}/register/sector`"
          target="_blank"
          class="flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium text-white/50 transition-colors hover:bg-white/10 hover:text-white"
        >
          Sell with us
          <svg
            class="size-3 opacity-60"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"
            />
          </svg>
        </a>
      </nav>

      <!-- Right utilities -->
      <div class="flex items-center gap-1.5">
        <!-- Search (desktop) -->
        <RouterLink
          to="/stores?focus=1"
          class="hidden items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white/60 transition-colors hover:border-white/30 hover:bg-white/20 hover:text-white md:flex"
          aria-label="Search stores"
        >
          <MagnifyingGlassIcon class="size-3.5 shrink-0" />
          <span class="text-xs">Search…</span>
          <kbd
            class="ml-1 hidden rounded border border-white/20 bg-white/10 px-1 py-0.5 text-[10px] text-white/40 lg:inline"
            >/</kbd
          >
        </RouterLink>

        <!-- Cart -->
        <button
          type="button"
          class="relative rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white transition-colors"
          aria-label="Shopping cart"
          @click="cart.toggleDrawer"
        >
          <ShoppingCartIcon class="size-5" />
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="scale-50 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-50 opacity-0"
          >
            <span
              v-if="cart.totalQuantity > 0"
              class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white ring-2 ring-[#0F2044]"
            >
              {{ cart.totalQuantity > 9 ? "9+" : cart.totalQuantity }}
            </span>
          </Transition>
        </button>

        <!-- Guest -->
        <template v-if="!auth.isLoggedIn">
          <RouterLink
            to="/login"
            class="hidden rounded-lg px-3 py-2 text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white transition-colors sm:block"
          >
            Sign in
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 hover:shadow-emerald-500/25 hover:shadow-md active:scale-[0.98] transition-all"
          >
            Register
          </RouterLink>
        </template>

        <!-- Logged in -->
        <template v-else>
          <RouterLink
            to="/account"
            class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white transition-colors sm:flex"
          >
            <UserCircleIcon class="size-4.5" />
            Account
          </RouterLink>
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm font-medium text-white/40 hover:bg-red-500/10 hover:text-red-400 transition-colors"
            @click="handleLogout"
          >
            Sign out
          </button>
        </template>

        <!-- Mobile toggle -->
        <button
          type="button"
          class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white transition-colors md:hidden"
          :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
          @click="mobileOpen = !mobileOpen"
        >
          <XMarkIcon v-if="mobileOpen" class="size-5" />
          <Bars3Icon v-else class="size-5" />
        </button>
      </div>
    </div>

    <!-- Mobile nav -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <nav
        v-if="mobileOpen"
        class="border-t border-white/10 bg-navy-900 px-4 py-2 md:hidden"
      >
        <RouterLink
          v-for="sector in sectors"
          :key="sector.to"
          :to="sector.to"
          class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
          :class="
            isActive(sector.to)
              ? 'bg-white/10 text-white'
              : 'text-white/70 hover:bg-white/10 hover:text-white'
          "
          @click="mobileOpen = false"
        >
          {{ sector.label }}
        </RouterLink>

        <div class="mt-2 border-t border-white/10 pt-2">
          <RouterLink
            v-if="auth.isLoggedIn"
            to="/account"
            class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white"
            @click="mobileOpen = false"
          >
            <UserCircleIcon class="size-4" />
            My Account
          </RouterLink>
          <template v-else>
            <RouterLink
              to="/login"
              class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white"
              @click="mobileOpen = false"
            >
              Sign in
            </RouterLink>
            <RouterLink
              to="/register"
              class="mt-1 flex items-center rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500"
              @click="mobileOpen = false"
            >
              Create account
            </RouterLink>
          </template>
          <a
            :href="`${backendUrl}/register/sector`"
            target="_blank"
            class="mt-1 flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-white/50 hover:bg-white/10 hover:text-white"
            @click="mobileOpen = false"
          >
            Sell with us
          </a>
        </div>
      </nav>
    </Transition>
  </header>
</template>
