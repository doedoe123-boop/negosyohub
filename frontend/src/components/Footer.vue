<script setup>
const backendUrl = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8080";
const year = new Date().getFullYear();
</script>

<template>
  <footer class="border-t border-slate-800/60 bg-slate-950 text-slate-400">
    <div class="mx-auto max-w-7xl px-4 pt-14 pb-10 sm:px-6">
      <!-- Top grid -->
      <div class="grid grid-cols-2 gap-8 md:grid-cols-4 lg:gap-12">
        <!-- Brand -->
        <div class="col-span-2 md:col-span-1">
          <div class="mb-4 flex items-center gap-2.5">
            <span
              class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-sm"
            >
              N
            </span>
            <span class="text-base font-bold tracking-tight text-white"
              >Negosyo<span class="text-brand-400">Hub</span></span
            >
          </div>
          <p class="text-sm leading-relaxed text-slate-500">
            Multi-sector marketplace connecting buyers with trusted local
            Philippine businesses — e-commerce, real estate, and more.
          </p>
          <!-- Social links -->
          <div class="mt-5 flex items-center gap-3">
            <a
              href="#"
              aria-label="Facebook"
              class="flex size-8 items-center justify-center rounded-lg bg-slate-800 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
            >
              <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                />
              </svg>
            </a>
            <a
              href="#"
              aria-label="Instagram"
              class="flex size-8 items-center justify-center rounded-lg bg-slate-800 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
            >
              <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"
                />
              </svg>
            </a>
          </div>
        </div>

        <!-- Browse -->
        <div>
          <p
            class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            Browse
          </p>
          <ul class="space-y-2.5 text-sm">
            <li>
              <a href="/stores" class="transition-colors hover:text-white"
                >E-Commerce</a
              >
            </li>
            <li>
              <a href="/properties" class="transition-colors hover:text-white"
                >Real Estate</a
              >
            </li>
            <li>
              <a href="/movers" class="transition-colors hover:text-white"
                >Lipat Bahay</a
              >
            </li>
          </ul>
        </div>

        <!-- Sellers -->
        <div>
          <p
            class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            For Sellers
          </p>
          <ul class="space-y-2.5 text-sm">
            <li>
              <a
                :href="`${backendUrl}/register/sector`"
                target="_blank"
                rel="noopener"
                class="transition-colors hover:text-white"
                >Register Your Store</a
              >
            </li>
            <li>
              <a
                :href="`${backendUrl}/store/dashboard`"
                target="_blank"
                rel="noopener"
                class="transition-colors hover:text-white"
                >Seller Dashboard</a
              >
            </li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <p
            class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-300"
          >
            Company
          </p>
          <ul class="space-y-2.5 text-sm">
            <li>
              <a
                :href="`${backendUrl}/legal/terms-and-conditions`"
                class="transition-colors hover:text-white"
                >Terms of Service</a
              >
            </li>
            <li>
              <a
                :href="`${backendUrl}/legal/privacy-policy`"
                class="transition-colors hover:text-white"
                >Privacy Policy</a
              >
            </li>
            <li class="text-slate-600">About Us</li>
          </ul>
        </div>
      </div>

      <!-- Trust badges -->
      <div
        class="mt-10 flex flex-wrap items-center gap-4 border-t border-slate-800/60 pt-8"
      >
        <div
          class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-xs text-slate-400"
        >
          <svg
            class="size-4 text-emerald-500 shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
            />
          </svg>
          Secure checkout
        </div>
        <div
          class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-xs text-slate-400"
        >
          <svg
            class="size-4 text-brand-400 shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"
            />
          </svg>
          PayMongo &amp; Stripe
        </div>
        <div
          class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-xs text-slate-400"
        >
          <span class="text-sm">🇵🇭</span>
          Made in the Philippines
        </div>
      </div>

      <!-- Copyright -->
      <div
        class="mt-8 flex flex-col items-center justify-between gap-2 text-xs text-slate-700 sm:flex-row"
      >
        <p>&copy; {{ year }} NegosyoHub. All rights reserved.</p>
        <p>Built with ❤️ in the Philippines</p>
      </div>
    </div>
  </footer>
</template>
