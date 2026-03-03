<script setup>
const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";
const year = new Date().getFullYear();
</script>

<template>
  <footer class="border-t border-slate-800 bg-slate-950 text-slate-400">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
      <!-- Top grid -->
      <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
        <!-- Brand -->
        <div class="col-span-2 md:col-span-1">
          <div class="mb-3 flex items-center gap-2">
            <span
              class="flex size-8 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white"
            >
              N
            </span>
            <span class="text-base font-bold text-white">NegosyoHub</span>
          </div>
          <p class="text-xs leading-relaxed text-slate-500">
            Multi-sector marketplace connecting buyers with trusted local
            Philippine businesses — e-commerce, real estate, and more.
          </p>
        </div>

        <!-- Browse -->
        <div>
          <p
            class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            Browse
          </p>
          <ul class="space-y-2 text-sm">
            <li>
              <a href="/stores" class="transition-colors hover:text-white"
                >Stores</a
              >
            </li>
            <li>
              <a href="/properties" class="transition-colors hover:text-white"
                >Properties</a
              >
            </li>
          </ul>
        </div>

        <!-- Sellers -->
        <div>
          <p
            class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            Sellers
          </p>
          <ul class="space-y-2 text-sm">
            <li>
              <a
                :href="`${backendUrl}/register/sector`"
                target="_blank"
                rel="noopener"
                class="transition-colors hover:text-white"
              >
                Register Your Store
              </a>
            </li>
            <li>
              <a
                :href="`${backendUrl}/store/dashboard`"
                target="_blank"
                rel="noopener"
                class="transition-colors hover:text-white"
              >
                Store Dashboard
              </a>
            </li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <p
            class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            Legal
          </p>
          <ul class="space-y-2 text-sm">
            <li>
              <a
                :href="`${backendUrl}/legal/terms-and-conditions`"
                class="transition-colors hover:text-white"
              >
                Terms of Service
              </a>
            </li>
            <li>
              <a
                :href="`${backendUrl}/legal/privacy-policy`"
                class="transition-colors hover:text-white"
              >
                Privacy Policy
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Divider + copyright -->
      <div
        class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-800 pt-6 text-xs text-slate-600 sm:flex-row"
      >
        <p>&copy; {{ year }} NegosyoHub. All rights reserved.</p>
        <p class="text-slate-700">Built with ❤️ in the Philippines</p>
      </div>
    </div>
  </footer>
</template>
