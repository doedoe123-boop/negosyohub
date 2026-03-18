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
  <div class="bg-slate-50">
    <!-- Mobile / tablet tab strip (hidden on lg+) -->
    <div
      class="sticky top-0 z-10 border-b border-slate-200 bg-white shadow-sm lg:hidden"
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
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
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
        class="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-6 lg:block"
        style="min-height: calc(100vh - 64px)"
      >
        <!-- User card -->
        <div class="mb-5 flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
          <div
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white"
          >
            {{ auth.user?.name?.[0]?.toUpperCase() ?? "?" }}
          </div>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">
              {{ auth.user?.name }}
            </p>
            <p class="truncate text-xs text-slate-400">
              {{ auth.user?.email }}
            </p>
            <p v-if="auth.user?.phone" class="truncate text-xs text-slate-400">
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
            :class="
              isActive(item)
                ? 'bg-brand-50 text-brand-700'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
            "
          >
            <component
              :is="item.icon"
              class="size-4.5 shrink-0 transition-colors"
              :class="isActive(item) ? 'text-brand-600' : 'text-slate-400'"
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
