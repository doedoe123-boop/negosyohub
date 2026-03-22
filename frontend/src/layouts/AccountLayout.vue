<script setup>
import { RouterView, RouterLink, useRoute } from "vue-router";
import {
  HomeIcon,
  ShoppingBagIcon,
  HeartIcon,
  BookmarkIcon,
  MapPinIcon,
  CreditCardIcon,
  UserCircleIcon,
  KeyIcon,
  Cog6ToothIcon,
  TruckIcon,
  DocumentTextIcon,
  LifebuoyIcon,
} from "@heroicons/vue/24/outline";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const route = useRoute();

const navItems = [
  { label: "Overview", to: "/account", icon: HomeIcon, exact: true },
  { label: "My Orders", to: "/account/orders", icon: ShoppingBagIcon },
  { label: "My Inquiries", to: "/account/inquiries", icon: HeartIcon },
  {
    label: "Saved Searches",
    to: "/account/saved-searches",
    icon: BookmarkIcon,
  },
  { label: "Agreements", to: "/account/agreements", icon: DocumentTextIcon },
  { label: "Moving", to: "/account/moving", icon: TruckIcon },
  { label: "Addresses", to: "/account/addresses", icon: MapPinIcon },
  {
    label: "Payment Methods",
    to: "/account/payment-methods",
    icon: CreditCardIcon,
  },
  { label: "Profile", to: "/account/profile", icon: UserCircleIcon },
  { label: "Password", to: "/account/password", icon: KeyIcon },
  { label: "Help & Support", to: "/account/help", icon: LifebuoyIcon },
  { label: "Settings", to: "/account/settings", icon: Cog6ToothIcon },
];

function isActive(item) {
  if (item.exact) {
    return route.path === item.to;
  }

  return route.path === item.to || route.path.startsWith(item.to + "/");
}
</script>

<template>
  <div class="theme-app transition-colors">
    <!-- Mobile / tablet tab strip (hidden on lg+) -->
    <div
      class="theme-surface sticky top-0 z-10 rounded-none border-x-0 border-t-0 lg:hidden"
    >
      <div class="overflow-x-auto">
        <div class="flex min-w-max px-4 sm:px-6">
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-3.5 text-xs font-medium whitespace-nowrap transition-colors"
            :class="
              isActive(item)
                ? 'border-brand-600 text-brand-700'
                : 'border-transparent'
            "
            :style="
              isActive(item)
                ? ''
                : 'color: var(--color-text-muted); border-color: transparent'
            "
          >
            <component :is="item.icon" class="size-4 shrink-0" />
            {{ item.label }}
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Page body -->
    <div class="mx-auto max-w-7xl lg:flex">
      <!-- Desktop sidebar -->
      <aside
        class="hidden w-64 shrink-0 border-r p-6 lg:block"
        style="
          border-color: var(--color-border);
          background-color: var(--color-surface);
          min-height: calc(100vh - 64px);
        "
      >
        <!-- User card -->
        <div class="theme-surface-muted mb-5 flex items-center gap-3 rounded-2xl p-3">
          <div
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white"
          >
            {{ auth.user?.name?.[0]?.toUpperCase() ?? "?" }}
          </div>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold" style="color: var(--color-text)">
              {{ auth.user?.name }}
            </p>
            <p class="truncate text-xs theme-text-muted">
              {{ auth.user?.email }}
            </p>
            <p v-if="auth.user?.phone" class="truncate text-xs theme-text-muted">
              {{ auth.user?.phone }}
            </p>
          </div>
        </div>

        <!-- Nav -->
        <nav class="space-y-0.5">
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
            :style="
              isActive(item)
                ? 'background-color: color-mix(in srgb, var(--color-brand) 14%, var(--color-surface)); color: var(--color-text); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-brand) 24%, var(--color-border));'
                : 'color: var(--color-text-muted)'
            "
            :class="isActive(item) ? '' : 'hover:bg-[var(--color-surface-muted)]'"
          >
            <component
              :is="item.icon"
              class="size-4.5 shrink-0 transition-colors"
              :style="
                isActive(item)
                  ? 'color: var(--color-brand)'
                  : 'color: var(--color-text-muted)'
              "
            />
            {{ item.label }}
          </RouterLink>
        </nav>
      </aside>

      <!-- Main content -->
      <main class="min-w-0 flex-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>
