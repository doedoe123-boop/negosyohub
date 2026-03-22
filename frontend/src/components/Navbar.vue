<script setup>
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  ShoppingCartIcon,
  Bars3Icon,
  XMarkIcon,
  UserCircleIcon,
  MagnifyingGlassIcon,
  MoonIcon,
  SunIcon,
} from "@heroicons/vue/24/outline";
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";
import { useTheme } from "@/composables/useTheme";
import { searchApi } from "@/api/search";
import { useAppI18n } from "@/i18n";

const auth = useAuthStore();
const cart = useCartStore();
const { isDark, toggleTheme } = useTheme();
const { locale, setLocale, t } = useAppI18n();
const route = useRoute();
const router = useRouter();

async function handleLogout() {
  await auth.logout();
  router.push("/");
}

async function toggleLocale() {
  const nextLocale = locale.value === "en" ? "fil" : "en";
  await setLocale(nextLocale);

  if (auth.isLoggedIn) {
    await auth.persistPreferredLocale(nextLocale);
  }
}
const mobileOpen = ref(false);
const storesFlyout = ref(false);
const searchOpen = ref(false);
const searchQuery = ref("");
const searchInputRef = ref(null);
const searchLoading = ref(false);
const searchResults = ref(null);
let searchDebounce = null;

function openSearch() {
  searchOpen.value = true;
  searchQuery.value = "";
  searchResults.value = null;
  import("vue").then(({ nextTick }) =>
    nextTick(() => searchInputRef.value?.focus()),
  );
}

function closeSearch() {
  searchOpen.value = false;
  searchQuery.value = "";
  searchResults.value = null;
}

// Live search debounce
watch(searchQuery, (q) => {
  clearTimeout(searchDebounce);
  const trimmed = q.trim();
  if (!trimmed || trimmed.length < 2) {
    searchResults.value = null;
    return;
  }
  searchDebounce = setTimeout(async () => {
    searchLoading.value = true;
    try {
      const { data } = await searchApi.global({ q: trimmed, per_section: 4 });
      searchResults.value = data;
    } catch {
      searchResults.value = null;
    } finally {
      searchLoading.value = false;
    }
  }, 300);
});

function submitSearch() {
  if (!searchQuery.value.trim()) return;
  router.push({ path: "/stores", query: { search: searchQuery.value.trim() } });
  closeSearch();
}

function goToSearchResult(type, idOrSlug) {
  closeSearch();
  if (type === "store") router.push(`/stores/${idOrSlug}`);
  else if (type === "product") router.push(`/products/${idOrSlug}`);
  else if (type === "property") router.push(`/properties/${idOrSlug}`);
}

const navbarListingLabels = {
  for_sale: "For Sale",
  for_rent: "For Rent",
  for_lease: "For Lease",
  pre_selling: "Pre-Selling",
};

function formatNavPrice(price, currency = "PHP") {
  if (!price && price !== 0) return "";
  return parseFloat(price).toLocaleString("en-PH", {
    style: "currency",
    currency: currency || "PHP",
    maximumFractionDigits: 0,
  });
}

const hasNavResults = () => {
  if (!searchResults.value) return false;
  return (
    searchResults.value.stores?.length > 0 ||
    searchResults.value.products?.length > 0 ||
    searchResults.value.properties?.length > 0
  );
};

function onKeydown(e) {
  if (e.key === "/" && !e.ctrlKey && !e.metaKey && !e.altKey) {
    const tag = document.activeElement?.tagName?.toLowerCase();
    if (tag !== "input" && tag !== "textarea" && tag !== "select") {
      e.preventDefault();
      openSearch();
    }
  }
  if (e.key === "Escape" && searchOpen.value) {
    closeSearch();
  }
}

onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));

const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";

const sectorLinks = [
  { label: "E-Commerce", to: "/stores", icon: "🛍️" },
  { label: "Real Estate", to: "/properties", icon: "🏠" },
  { label: "Lipat Bahay", to: "/movers", icon: "🚚" },
  { label: "Services", to: "/stores?sector=services", icon: "🔧" },
];

function isActive(path) {
  return route.path.startsWith(path);
}
</script>

<template>
  <header class="nav-shell sticky top-0 z-40 shadow-md">
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
        <!-- Sectors with flyout -->
        <div
          class="relative"
          @mouseenter="storesFlyout = true"
          @mouseleave="storesFlyout = false"
        >
          <button
            type="button"
            class="relative flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
            :class="
              isActive('/stores') ||
              isActive('/properties') ||
              isActive('/movers')
                ? 'nav-button-active'
                : 'nav-button'
            "
          >
            {{ t("nav.sectors") }}
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
              v-if="
                isActive('/stores') ||
                isActive('/properties') ||
                isActive('/movers')
              "
              class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-emerald-400"
            />
          </button>

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
                class="theme-modal w-48 overflow-hidden rounded-2xl py-2 ring-1 ring-black/5"
              >
                <RouterLink
                  v-for="s in sectorLinks"
                  :key="s.label"
                  :to="s.to"
                  class="flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                  style="color: var(--color-text)"
                  @click="storesFlyout = false"
                >
                  <span class="text-base">{{ s.icon }}</span>
                  {{ s.label }}
                </RouterLink>
              </div>
            </div>
          </Transition>
        </div>

        <span
          class="mx-1 h-4 w-px"
          style="background-color: var(--color-navbar-border)"
        />

        <a
          :href="`${backendUrl}/register/sector`"
          target="_blank"
          class="nav-button flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        >
          {{ t("nav.sellWithUs") }}
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
        <button
          type="button"
          class="nav-button rounded-lg px-2.5 py-2 text-xs font-bold uppercase transition-colors"
          @click="toggleLocale"
        >
          {{ locale === "en" ? "FIL" : "EN" }}
        </button>

        <!-- Dark Mode Toggle -->
        <button
          type="button"
          class="nav-button rounded-lg p-2 transition-colors"
          aria-label="Toggle dark mode"
          @click="toggleTheme"
        >
          <MoonIcon v-if="!isDark" class="size-5" />
          <SunIcon v-else class="size-5" />
        </button>

        <!-- Search (desktop) -->
        <button
          type="button"
          class="nav-search-trigger hidden items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors md:flex"
          aria-label="Search stores"
          @click="openSearch"
        >
          <MagnifyingGlassIcon class="size-3.5 shrink-0" />
          <span class="text-xs">{{ t("nav.search") }}</span>
          <kbd
            class="ml-1 hidden rounded border px-1 py-0.5 text-[10px] lg:inline"
            style="
              border-color: var(--color-navbar-border);
              background-color: var(--color-navbar-muted-surface);
              color: color-mix(in srgb, var(--color-navbar-text) 70%, transparent);
            "
            >/</kbd
          >
        </button>

        <!-- Cart -->
        <button
          type="button"
          class="nav-button relative rounded-lg p-2 transition-colors"
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
              v-if="cart.lineCount > 0"
              class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white"
              style="box-shadow: 0 0 0 2px var(--color-navbar-surface)"
            >
              {{ cart.lineCount > 9 ? "9+" : cart.lineCount }}
            </span>
          </Transition>
        </button>

        <!-- Guest -->
        <template v-if="!auth.isLoggedIn">
          <RouterLink
            to="/login"
            class="nav-button hidden rounded-lg px-3 py-2 text-sm font-medium transition-colors sm:block"
          >
            {{ t("nav.signIn") }}
          </RouterLink>
          <RouterLink
            to="/register"
            class="btn-primary rounded-lg px-4 py-2 text-sm font-bold active:scale-[0.98] transition-all"
          >
            {{ t("nav.register") }}
          </RouterLink>
        </template>

        <!-- Logged in -->
        <template v-else>
          <RouterLink
            to="/account"
            class="nav-button hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors sm:flex"
          >
            <UserCircleIcon class="size-4.5" />
            {{ t("nav.account") }}
          </RouterLink>
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            style="
              color: color-mix(in srgb, var(--color-navbar-text) 68%, transparent);
            "
            @click="handleLogout"
          >
            {{ t("nav.signOut") }}
          </button>
        </template>

        <!-- Mobile toggle -->
        <button
          type="button"
          class="nav-button rounded-lg p-2 transition-colors md:hidden"
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
        class="nav-shell border-t px-4 py-2 md:hidden"
      >
        <RouterLink
          v-for="s in sectorLinks"
          :key="s.label"
          :to="s.to"
          class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
          :class="
            isActive(s.to.split('?')[0])
              ? 'nav-button-active'
              : 'nav-button'
          "
          @click="mobileOpen = false"
        >
          <span>{{ s.icon }}</span>
          {{ s.label }}
        </RouterLink>

        <div
          class="mt-2 border-t pt-2"
          style="border-color: var(--color-navbar-border)"
        >
          <RouterLink
            v-if="auth.isLoggedIn"
            to="/account"
            class="nav-button flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium"
            @click="mobileOpen = false"
          >
            <UserCircleIcon class="size-4" />
            My Account
          </RouterLink>
          <template v-else>
            <RouterLink
              to="/login"
              class="nav-button flex items-center rounded-xl px-3 py-2.5 text-sm font-medium"
              @click="mobileOpen = false"
            >
              Sign in
            </RouterLink>
            <RouterLink
              to="/register"
              class="btn-primary mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold"
              @click="mobileOpen = false"
            >
              Create account
            </RouterLink>
          </template>
          <a
            :href="`${backendUrl}/register/sector`"
            target="_blank"
            class="nav-button mt-1 flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium"
            @click="mobileOpen = false"
          >
            Sell with us
          </a>
        </div>
      </nav>
    </Transition>
  </header>

  <!-- Global search overlay -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="searchOpen"
        class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
        @click.self="closeSearch"
      >
        <!-- Backdrop -->
        <div
          class="theme-overlay absolute inset-0"
          @click="closeSearch"
        />

        <!-- Modal -->
        <div class="relative w-full max-w-xl">
          <form @submit.prevent="submitSearch">
            <div
              class="theme-modal flex items-center gap-3 rounded-t-2xl px-4 py-3"
              :class="
                searchResults && searchQuery.trim().length >= 2
                  ? ''
                  : 'rounded-b-2xl'
              "
            >
              <MagnifyingGlassIcon
                class="size-5 shrink-0"
                style="color: var(--color-text-muted)"
              />
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                type="search"
                placeholder="Search stores, products, properties…"
                autocomplete="off"
                class="flex-1 bg-transparent text-base outline-none"
                style="color: var(--color-text)"
              />
              <svg
                v-if="searchLoading"
                class="size-5 shrink-0 animate-spin text-emerald-500"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
              <kbd
                v-else
                class="hidden rounded-lg px-2 py-1 text-xs sm:block"
                style="
                  border: 1px solid var(--color-border);
                  background-color: var(--color-surface-muted);
                  color: var(--color-text-muted);
                "
                >ESC</kbd
              >
            </div>
          </form>

          <!-- Live results dropdown -->
          <div
            v-if="searchResults && searchQuery.trim().length >= 2"
            class="theme-modal max-h-80 overflow-y-auto rounded-b-2xl border-t-0"
          >
            <!-- No results -->
            <div
              v-if="!hasNavResults()"
              class="py-6 text-center text-sm"
              style="color: var(--color-text-muted)"
            >
              <p class="text-lg mb-1">🔍</p>
              No results for "<span class="font-medium" style="color: var(--color-text)">{{
                searchQuery.trim()
              }}</span
              >"
            </div>

            <template v-else>
              <!-- Stores -->
              <div
                v-if="searchResults.stores?.length"
                class="theme-divider border-b"
              >
                <div
                  class="px-4 py-2"
                  style="background-color: color-mix(in srgb, var(--color-surface-muted) 88%, transparent)"
                >
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest"
                    style="color: var(--color-text-muted)"
                    >Stores</span
                  >
                </div>
                <button
                  v-for="s in searchResults.stores"
                  :key="'ns-' + s.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2 text-left hover:bg-emerald-50/50 transition-colors"
                  @click="goToSearchResult('store', s.slug)"
                >
                  <img
                    v-if="s.logo_url"
                    :src="s.logo_url"
                    :alt="s.name"
                    class="size-8 rounded-lg object-cover"
                    style="background-color: var(--color-surface-muted)"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg text-sm"
                    style="background-color: var(--color-surface-muted)"
                  >
                    🏪
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" style="color: var(--color-text)">
                      {{ s.name }}
                    </p>
                    <p class="truncate text-xs" style="color: var(--color-text-muted)">
                      <span
                        v-if="s.sector"
                        class="mr-1 rounded px-1 py-0.5 text-[10px] font-semibold"
                        style="
                          background-color: var(--color-surface-muted);
                          color: var(--color-text-muted);
                        "
                        >{{ s.sector_label ?? s.sector }}</span
                      >
                      {{ s.city ?? "" }}
                    </p>
                  </div>
                </button>
              </div>

              <!-- Products -->
              <div
                v-if="searchResults.products?.length"
                class="theme-divider border-b"
              >
                <div
                  class="px-4 py-2"
                  style="background-color: color-mix(in srgb, var(--color-surface-muted) 88%, transparent)"
                >
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest"
                    style="color: var(--color-text-muted)"
                    >Products</span
                  >
                </div>
                <button
                  v-for="p in searchResults.products"
                  :key="'np-' + p.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2 text-left hover:bg-emerald-50/50 transition-colors"
                  @click="goToSearchResult('product', p.id)"
                >
                  <img
                    v-if="p.thumbnail"
                    :src="p.thumbnail"
                    :alt="p.name"
                    class="size-8 rounded-lg object-cover"
                    style="background-color: var(--color-surface-muted)"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg text-sm"
                    style="background-color: var(--color-surface-muted)"
                  >
                    🛍️
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" style="color: var(--color-text)">
                      {{ p.name ?? "Untitled Product" }}
                    </p>
                    <p
                      v-if="p.price"
                      class="text-xs font-medium text-brand-500"
                    >
                      {{ formatNavPrice(p.price, p.currency) }}
                    </p>
                  </div>
                </button>
              </div>

              <!-- Properties -->
              <div v-if="searchResults.properties?.length">
                <div
                  class="px-4 py-2"
                  style="background-color: color-mix(in srgb, var(--color-surface-muted) 88%, transparent)"
                >
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest"
                    style="color: var(--color-text-muted)"
                    >Properties</span
                  >
                </div>
                <button
                  v-for="pr in searchResults.properties"
                  :key="'npr-' + pr.id"
                  type="button"
                  class="flex w-full items-center gap-3 px-4 py-2 text-left hover:bg-emerald-50/50 transition-colors"
                  @click="goToSearchResult('property', pr.slug)"
                >
                  <img
                    v-if="pr.images?.[0]"
                    :src="pr.images[0]"
                    :alt="pr.title"
                    class="size-8 rounded-lg object-cover"
                    style="background-color: var(--color-surface-muted)"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg text-sm"
                    style="background-color: var(--color-surface-muted)"
                  >
                    🏠
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" style="color: var(--color-text)">
                      {{ pr.title }}
                    </p>
                    <p class="truncate text-xs" style="color: var(--color-text-muted)">
                      <span
                        v-if="pr.listing_type"
                        class="mr-1 rounded bg-emerald-50 px-1 py-0.5 text-[10px] font-semibold text-emerald-600"
                        >{{
                          navbarListingLabels[pr.listing_type] ??
                          pr.listing_type
                        }}</span
                      >
                      {{ pr.city ?? "" }}
                      <span
                        v-if="pr.price"
                        class="ml-1 font-medium text-brand-500"
                        >{{ formatNavPrice(pr.price, pr.price_currency) }}</span
                      >
                    </p>
                  </div>
                </button>
              </div>
            </template>
          </div>

          <p
            class="mt-2 text-center text-xs"
            style="color: color-mix(in srgb, var(--color-navbar-text) 72%, transparent)"
          >
            Press
            <kbd
              class="rounded px-1 py-0.5"
              style="background-color: var(--color-navbar-muted-surface)"
              >Enter</kbd
            >
            to
            search all ·
            <kbd
              class="rounded px-1 py-0.5"
              style="background-color: var(--color-navbar-muted-surface)"
              >Esc</kbd
            >
            to close
          </p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
