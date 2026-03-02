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
// (remove this if you want to see Vue warnings)
vi.spyOn(console, "error").mockImplementation(() => {});
