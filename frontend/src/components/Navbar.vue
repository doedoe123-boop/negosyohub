<script setup>
import { RouterLink, useRoute, useRouter } from "vue-router";
import {
  ShoppingCartIcon,
  Bars3Icon,
  XMarkIcon,
  UserCircleIcon,
  MagnifyingGlassIcon,
} from "@heroicons/vue/24/outline";
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";
import { searchApi } from "@/api/search";

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
                ? 'bg-white/10 text-white'
                : 'text-white/70 hover:bg-white/10 hover:text-white'
            "
          >
            Sectors
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
                class="w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-xl ring-1 ring-black/5"
              >
                <RouterLink
                  v-for="s in sectorLinks"
                  :key="s.label"
                  :to="s.to"
                  class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                  @click="storesFlyout = false"
                >
                  <span class="text-base">{{ s.icon }}</span>
                  {{ s.label }}
                </RouterLink>
              </div>
            </div>
          </Transition>
        </div>

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
        <button
          type="button"
          class="hidden items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white/60 transition-colors hover:border-white/30 hover:bg-white/20 hover:text-white md:flex"
          aria-label="Search stores"
          @click="openSearch"
        >
          <MagnifyingGlassIcon class="size-3.5 shrink-0" />
          <span class="text-xs">Search…</span>
          <kbd
            class="ml-1 hidden rounded border border-white/20 bg-white/10 px-1 py-0.5 text-[10px] text-white/40 lg:inline"
            >/</kbd
          >
        </button>

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
              v-if="cart.lineCount > 0"
              class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white ring-2 ring-[#0F2044]"
            >
              {{ cart.lineCount > 9 ? "9+" : cart.lineCount }}
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
          v-for="s in sectorLinks"
          :key="s.label"
          :to="s.to"
          class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
          :class="
            isActive(s.to.split('?')[0])
              ? 'bg-white/10 text-white'
              : 'text-white/70 hover:bg-white/10 hover:text-white'
          "
          @click="mobileOpen = false"
        >
          <span>{{ s.icon }}</span>
          {{ s.label }}
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
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="closeSearch"
        />

        <!-- Modal -->
        <div class="relative w-full max-w-xl">
          <form @submit.prevent="submitSearch">
            <div
              class="flex items-center gap-3 rounded-t-2xl border border-slate-200 bg-white px-4 py-3 shadow-2xl"
              :class="
                searchResults && searchQuery.trim().length >= 2
                  ? ''
                  : 'rounded-b-2xl'
              "
            >
              <MagnifyingGlassIcon class="size-5 shrink-0 text-slate-400" />
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                type="search"
                placeholder="Search stores, products, properties…"
                autocomplete="off"
                class="flex-1 text-base text-slate-800 placeholder-slate-400 outline-none"
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
                class="hidden rounded-lg border border-slate-200 bg-slate-100 px-2 py-1 text-xs text-slate-400 sm:block"
                >ESC</kbd
              >
            </div>
          </form>

          <!-- Live results dropdown -->
          <div
            v-if="searchResults && searchQuery.trim().length >= 2"
            class="max-h-80 overflow-y-auto rounded-b-2xl border border-t-0 border-slate-200 bg-white shadow-2xl"
          >
            <!-- No results -->
            <div
              v-if="!hasNavResults()"
              class="py-6 text-center text-sm text-slate-400"
            >
              <p class="text-lg mb-1">🔍</p>
              No results for "<span class="font-medium text-slate-600">{{
                searchQuery.trim()
              }}</span
              >"
            </div>

            <template v-else>
              <!-- Stores -->
              <div
                v-if="searchResults.stores?.length"
                class="border-b border-slate-100"
              >
                <div class="px-4 py-2 bg-slate-50/80">
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400"
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
                    class="size-8 rounded-lg bg-slate-100 object-cover"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🏪
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ s.name }}
                    </p>
                    <p class="truncate text-xs text-slate-400">
                      <span
                        v-if="s.sector"
                        class="mr-1 rounded bg-slate-100 px-1 py-0.5 text-[10px] font-semibold text-slate-500"
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
                class="border-b border-slate-100"
              >
                <div class="px-4 py-2 bg-slate-50/80">
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400"
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
                    class="size-8 rounded-lg bg-slate-100 object-cover"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🛍️
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
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
                <div class="px-4 py-2 bg-slate-50/80">
                  <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400"
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
                    class="size-8 rounded-lg bg-slate-100 object-cover"
                  />
                  <div
                    v-else
                    class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-sm"
                  >
                    🏠
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ pr.title }}
                    </p>
                    <p class="truncate text-xs text-slate-400">
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

          <p class="mt-2 text-center text-xs text-white/50">
            Press <kbd class="rounded bg-white/20 px-1 py-0.5">Enter</kbd> to
            search all ·
            <kbd class="rounded bg-white/20 px-1 py-0.5">Esc</kbd> to close
          </p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
