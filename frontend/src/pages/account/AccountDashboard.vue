<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  ShoppingBagIcon,
  MapPinIcon,
  CreditCardIcon,
  UserCircleIcon,
  ClockIcon,
  ChevronRightIcon,
  Cog6ToothIcon,
  HomeModernIcon,
  TruckIcon,
  HeartIcon,
  DocumentTextIcon,
  BellIcon,
  BellAlertIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { useAuthStore } from "@/stores/auth";
import { ordersApi } from "@/api/orders";
import { inquiriesApi } from "@/api/inquiries";
import { movingBookingsApi } from "@/api/movingBookings";
import { notificationsApi } from "@/api/notifications";

const auth = useAuthStore();
const recentOrders = ref([]);
const recentInquiries = ref([]);
const recentBookings = ref([]);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(true);
const error = ref(false);

onMounted(async () => {
  try {
    const [ordersRes, inquiriesRes, bookingsRes, notifRes] =
      await Promise.allSettled([
        ordersApi.list(),
        inquiriesApi.list(),
        movingBookingsApi.list(),
        notificationsApi.list(),
      ]);

    if (ordersRes.status === "fulfilled") {
      const all = ordersRes.value.data.data ?? ordersRes.value.data;
      recentOrders.value = all.slice(0, 3);
    }
    if (inquiriesRes.status === "fulfilled") {
      const all = inquiriesRes.value.data.data ?? inquiriesRes.value.data;
      recentInquiries.value = all.slice(0, 3);
    }
    if (bookingsRes.status === "fulfilled") {
      const all = bookingsRes.value.data.data ?? bookingsRes.value.data;
      recentBookings.value = all.slice(0, 3);
    }
    if (notifRes.status === "fulfilled") {
      notifications.value = notifRes.value.data.notifications ?? [];
      unreadCount.value = notifRes.value.data.unread_count ?? 0;
    }
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

const statusColors = {
  pending: "bg-yellow-100 text-yellow-700",
  confirmed: "bg-blue-100 text-blue-700",
  preparing: "bg-purple-100 text-purple-700",
  ready: "bg-indigo-100 text-indigo-700",
  delivered: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};

const inquiryStatusColors = {
  new: "bg-blue-100 text-blue-700",
  contacted: "bg-yellow-100 text-yellow-700",
  viewing_scheduled: "bg-purple-100 text-purple-700",
  negotiating: "bg-emerald-100 text-emerald-700",
  closed: "theme-badge-neutral",
};

const bookingStatusColors = {
  pending: "bg-yellow-100 text-yellow-700",
  confirmed: "bg-blue-100 text-blue-700",
  in_progress: "bg-purple-100 text-purple-700",
  completed: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};

async function dismissNotification(id) {
  notifications.value = notifications.value.filter((n) => n.id !== id);
  unreadCount.value = Math.max(0, unreadCount.value - 1);
  try {
    await notificationsApi.markRead(id);
  } catch {
    // Silent fail – already removed from UI
  }
}

async function dismissAllNotifications() {
  notifications.value = [];
  unreadCount.value = 0;
  try {
    await notificationsApi.markAllRead();
  } catch {
    // Silent fail
  }
}

const quickLinks = [
  {
    to: "/account/orders",
    label: "My Orders",
    description: "Track and view your orders",
    icon: ShoppingBagIcon,
    color: "bg-brand-50 text-brand-600 group-hover:bg-brand-100",
  },
  {
    to: "/account/inquiries",
    label: "My Inquiries",
    description: "Property interest & viewings",
    icon: HeartIcon,
    color: "bg-rose-50 text-rose-600 group-hover:bg-rose-100",
  },
  {
    to: "/account/agreements",
    label: "Rental Agreements",
    description: "Your signed contracts",
    icon: DocumentTextIcon,
    color: "bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100",
  },
  {
    to: "/account/moving",
    label: "Moving Bookings",
    description: "Lipat-bahay service bookings",
    icon: TruckIcon,
    color: "bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100",
  },
  {
    to: "/account/addresses",
    label: "Addresses",
    description: "Manage delivery addresses",
    icon: MapPinIcon,
    color: "bg-green-50 text-green-600 group-hover:bg-green-100",
  },
  {
    to: "/account/payment-methods",
    label: "Payment Methods",
    description: "Saved cards and billing",
    icon: CreditCardIcon,
    color: "bg-violet-50 text-violet-600 group-hover:bg-violet-100",
  },
  {
    to: "/account/profile",
    label: "My Profile",
    description: "Name, email and phone",
    icon: UserCircleIcon,
    color: "theme-icon-muted theme-copy",
  },
  {
    to: "/account/settings",
    label: "Settings",
    description: "Notifications and account",
    icon: Cog6ToothIcon,
    color: "bg-orange-50 text-orange-600 group-hover:bg-orange-100",
  },
];
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <!-- Header -->
    <div class="mb-8">
      <p class="theme-copy text-sm">Welcome back,</p>
      <h1 class="theme-title text-3xl font-extrabold tracking-tight">
        {{ auth.user?.name }}
      </h1>
      <div
        class="theme-copy mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm"
      >
        <span>{{ auth.user?.email }}</span>
        <span v-if="auth.user?.phone" class="flex items-center gap-1">
          <span class="opacity-60">·</span>
          {{ auth.user?.phone }}
        </span>
      </div>
    </div>

    <!-- Notifications banner -->
    <div v-if="notifications.length" class="mb-6">
      <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <BellAlertIcon class="size-4.5 text-brand-500" />
          <h2 class="theme-title text-sm font-bold">
            Notifications
            <span
              class="ml-1 inline-flex size-5 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white"
            >
              {{ unreadCount }}
            </span>
          </h2>
        </div>
        <button
          class="text-xs font-medium text-brand-600 transition-colors hover:text-brand-700"
          @click="dismissAllNotifications"
        >
          Dismiss all
        </button>
      </div>

      <ul class="space-y-2">
        <li
          v-for="notif in notifications"
          :key="notif.id"
          class="group flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/50 p-3"
        >
          <BellIcon class="mt-0.5 size-4 shrink-0 text-brand-400" />
          <div class="min-w-0 flex-1">
            <p class="theme-title text-sm font-semibold">
              {{ notif.data.title }}
            </p>
            <p class="theme-copy text-xs">{{ notif.data.body }}</p>
          </div>
          <button
            class="theme-copy shrink-0 rounded-lg p-1 transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text)]"
            title="Dismiss"
            @click="dismissNotification(notif.id)"
          >
            <XMarkIcon class="size-4" />
          </button>
        </li>
      </ul>
    </div>

    <!-- Quick links grid -->
    <div class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <RouterLink
        v-for="link in quickLinks"
        :key="link.to"
        :to="link.to"
        class="theme-card theme-card-hover group flex items-center gap-4 rounded-2xl p-4"
      >
        <div
          class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors"
          :class="link.color"
        >
          <component :is="link.icon" class="size-5" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="theme-title font-semibold">{{ link.label }}</p>
          <p class="theme-copy truncate text-xs">{{ link.description }}</p>
        </div>
        <ChevronRightIcon
          class="theme-copy size-4 shrink-0 transition-colors group-hover:text-brand-500"
        />
      </RouterLink>
    </div>

    <!-- ── Recent Activity Sections ─────────────────────────────── -->
    <div class="space-y-8">
      <!-- Skeleton (shared) -->
      <div v-if="loading" class="space-y-3">
        <div
          v-for="i in 3"
          :key="i"
          class="theme-skeleton h-16 animate-pulse rounded-2xl"
        />
      </div>

      <template v-else>
        <!-- Recent Orders -->
        <div>
          <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <ShoppingBagIcon class="theme-copy size-4.5" />
              <h2 class="theme-title text-base font-bold">Recent Orders</h2>
            </div>
            <RouterLink
              to="/account/orders"
              class="text-sm font-medium text-brand-600 transition-colors hover:text-brand-700"
            >
              View all →
            </RouterLink>
          </div>

          <div
            v-if="recentOrders.length === 0"
            class="theme-empty-state rounded-2xl py-8 text-center"
          >
            <ShoppingBagIcon class="theme-copy mx-auto mb-2 size-8" />
            <p class="theme-copy text-sm font-medium">No orders yet</p>
            <RouterLink
              to="/stores"
              class="btn-primary mt-3 inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold transition-all"
            >
              Browse Stores
            </RouterLink>
          </div>

          <ul v-else class="space-y-3">
            <li v-for="order in recentOrders" :key="order.id">
              <RouterLink
                :to="`/account/orders/${order.id}`"
                class="theme-card theme-card-hover group flex items-center justify-between gap-3 rounded-2xl p-4"
              >
                <div class="min-w-0">
                  <p class="theme-title font-semibold">
                    Order #{{ order.id }}
                  </p>
                  <p class="theme-copy text-xs">{{ order.created_at }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                    :class="
                      statusColors[order.status] ??
                      'theme-badge-neutral'
                    "
                  >
                    {{ order.status }}
                  </span>
                  <span class="theme-title hidden font-bold sm:block">{{
                    order.total?.formatted
                  }}</span>
                  <ChevronRightIcon
                    class="theme-copy size-4 transition-colors group-hover:text-brand-500"
                  />
                </div>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Recent Property Inquiries -->
        <div>
          <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <HeartIcon class="theme-copy size-4.5" />
              <h2 class="theme-title text-base font-bold">
                My Property Inquiries
              </h2>
            </div>
            <RouterLink
              to="/account/inquiries"
              class="text-sm font-medium text-brand-600 transition-colors hover:text-brand-700"
            >
              View all →
            </RouterLink>
          </div>

          <div
            v-if="recentInquiries.length === 0"
            class="theme-empty-state rounded-2xl py-8 text-center"
          >
            <HomeModernIcon class="theme-copy mx-auto mb-2 size-8" />
            <p class="theme-copy text-sm font-medium">No inquiries yet</p>
            <p class="theme-copy mt-1 text-xs">
              Browse properties and express your interest.
            </p>
            <RouterLink
              to="/properties"
              class="btn-primary mt-3 inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold transition-all"
            >
              Browse Properties
            </RouterLink>
          </div>

          <ul v-else class="space-y-3">
            <li v-for="inquiry in recentInquiries" :key="inquiry.id">
              <RouterLink
                :to="`/properties/${inquiry.property?.slug}`"
                class="theme-card theme-card-hover group flex items-center justify-between gap-3 rounded-2xl p-4"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <img
                    v-if="inquiry.property?.featured_image"
                    :src="inquiry.property.featured_image"
                    :alt="inquiry.property.title"
                    class="size-12 shrink-0 rounded-xl object-cover"
                  />
                  <div
                    v-else
                    class="theme-icon-muted flex size-12 shrink-0 items-center justify-center rounded-xl"
                  >
                    <HomeModernIcon class="theme-copy size-5" />
                  </div>
                  <div class="min-w-0">
                    <p class="theme-title truncate font-semibold">
                      {{ inquiry.property?.title }}
                    </p>
                    <p class="theme-copy text-xs">
                      {{ inquiry.store?.name }} ·
                      {{ inquiry.property?.city }}
                    </p>
                  </div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                    :class="
                      inquiryStatusColors[inquiry.status] ??
                      'theme-badge-neutral'
                    "
                  >
                    {{ inquiry.status_label }}
                  </span>
                  <ChevronRightIcon
                    class="theme-copy size-4 transition-colors group-hover:text-brand-500"
                  />
                </div>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Recent Moving Bookings -->
        <div v-if="recentBookings.length > 0">
          <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <TruckIcon class="theme-copy size-4.5" />
              <h2 class="theme-title text-base font-bold">
                Moving Bookings
              </h2>
            </div>
            <RouterLink
              to="/account/moving"
              class="text-sm font-medium text-brand-600 transition-colors hover:text-brand-700"
            >
              View all →
            </RouterLink>
          </div>

          <ul class="space-y-3">
            <li v-for="booking in recentBookings" :key="booking.id">
              <RouterLink
                :to="`/account/moving/${booking.id}`"
                class="theme-card theme-card-hover group flex items-center justify-between gap-3 rounded-2xl p-4"
              >
                <div class="min-w-0">
                  <p class="theme-title font-semibold">
                    Booking #{{ booking.id }}
                  </p>
                  <p class="theme-copy text-xs">
                    {{ booking.mover_name ?? booking.store?.name }} ·
                    {{ booking.moving_date }}
                  </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                    :class="
                      bookingStatusColors[booking.status] ??
                      'theme-badge-neutral'
                    "
                  >
                    {{ booking.status?.replace(/_/g, " ") }}
                  </span>
                  <ChevronRightIcon
                    class="theme-copy size-4 transition-colors group-hover:text-brand-500"
                  />
                </div>
              </RouterLink>
            </li>
          </ul>
        </div>
      </template>
    </div>
  </div>
</template>
