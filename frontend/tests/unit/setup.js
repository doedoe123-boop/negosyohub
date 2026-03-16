import { config } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, vi } from "vitest";

// Auto-create a fresh Pinia before each test
beforeEach(() => {
  setActivePinia(createPinia());
});

// Mock vue-router globally so components that use RouterLink / useRouter don't blow up
config.global.stubs = {
  RouterLink: { template: "<a><slot /></a>" },
  RouterView: { template: "<div />" },
};

// Suppress noisy console.error from Vue warnings in test output
vi.spyOn(console, "error").mockImplementation(() => {});

// Global stub for all Heroicons — avoids per-file boilerplate.
// Add new icons here as the app grows.
vi.mock("@heroicons/vue/24/outline", () => {
  const s = { template: "<svg />" };
  return {
    ArrowPathIcon: s,
    Bars3Icon: s,
    BuildingStorefrontIcon: s,
    CheckCircleIcon: s,
    ChevronRightIcon: s,
    ClockIcon: s,
    Cog6ToothIcon: s,
    CreditCardIcon: s,
    GlobeAltIcon: s,
    HomeIcon: s,
    HomeModernIcon: s,
    KeyIcon: s,
    LockClosedIcon: s,
    MagnifyingGlassIcon: s,
    MapPinIcon: s,
    PencilIcon: s,
    PhoneIcon: s,
    PlusIcon: s,
    ShieldCheckIcon: s,
    ShoppingBagIcon: s,
    ShoppingCartIcon: s,
    StarIcon: s,
    TrashIcon: s,
    TruckIcon: s,
    UserCircleIcon: s,
    XMarkIcon: s,
    ExclamationTriangleIcon: s,
    // Added for dashboard / inquiry / notification features
    BellIcon: s,
    BellAlertIcon: s,
    DocumentTextIcon: s,
    HeartIcon: s,
    PhotoIcon: s,
    LockClosedIcon: s,
    InformationCircleIcon: s,
    CheckBadgeIcon: s,
    LifebuoyIcon: s,
  };
});

vi.mock("@heroicons/vue/24/solid", () => {
  const s = { template: "<svg />" };
  return {
    CheckCircleIcon: s,
    ExclamationTriangleIcon: s,
    StarIcon: s,
    XCircleIcon: s,
  };
});
