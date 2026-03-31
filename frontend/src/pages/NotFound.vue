<script setup>
import { RouterLink } from "vue-router";
</script>

<template>
  <div class="theme-page min-h-screen">
    <section class="theme-page-section relative overflow-hidden">
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute left-1/2 top-[-10rem] h-[24rem] w-[24rem] -translate-x-1/2 rounded-full bg-brand-500/10 blur-3xl" />
        <div class="absolute bottom-[-8rem] right-[-6rem] h-[18rem] w-[18rem] rounded-full bg-sky-500/10 blur-3xl" />
      </div>

      <div class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid w-full gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-brand-500">
              Error 404
            </p>
            <h1 class="theme-title mt-4 text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">
              This page took a wrong turn.
            </h1>
            <p class="theme-copy mt-5 max-w-2xl text-base leading-7 sm:text-lg">
              The link may be outdated, the page may have moved, or the address may be incomplete.
              You can jump back into shopping, property search, or service booking from here.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <RouterLink
                to="/"
                class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-600"
              >
                Go to Homepage
              </RouterLink>
              <button
                type="button"
                class="theme-card inline-flex items-center justify-center rounded-2xl px-6 py-3 text-sm font-semibold transition-colors hover:border-brand-500/30 hover:text-brand-500"
                @click="$router.back()"
              >
                Go Back
              </button>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-3">
              <RouterLink
                to="/stores"
                class="theme-card theme-card-hover rounded-2xl p-4"
              >
                <p class="theme-title text-sm font-bold">Shop Products</p>
                <p class="theme-copy mt-1 text-sm">Browse stores and trending catalog picks.</p>
              </RouterLink>
              <RouterLink
                to="/properties"
                class="theme-card theme-card-hover rounded-2xl p-4"
              >
                <p class="theme-title text-sm font-bold">Find a Place</p>
                <p class="theme-copy mt-1 text-sm">Continue your rental and property search.</p>
              </RouterLink>
              <RouterLink
                to="/movers"
                class="theme-card theme-card-hover rounded-2xl p-4"
              >
                <p class="theme-title text-sm font-bold">Book a Mover</p>
                <p class="theme-copy mt-1 text-sm">Explore Lipat Bahay providers and schedules.</p>
              </RouterLink>
            </div>
          </div>

          <div class="theme-card relative overflow-hidden rounded-[2rem] p-8 sm:p-10">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-sky-500 to-brand-500" />
            <div class="flex items-center justify-between">
              <span class="rounded-full bg-brand-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-500">
                Not Found
              </span>
              <span class="theme-copy text-sm">NegosyoHub</span>
            </div>

            <div class="mt-8">
              <p class="text-7xl font-black tracking-tighter text-brand-500 sm:text-8xl">
                404
              </p>
              <div class="theme-card-muted mt-6 rounded-2xl p-5">
                <p class="theme-title text-sm font-bold">What usually helps</p>
                <ul class="theme-copy mt-3 space-y-3 text-sm leading-6">
                  <li>Check the URL for missing or extra characters.</li>
                  <li>Open the section again from the main navigation.</li>
                  <li>Use the homepage if you came from an old bookmark.</li>
                </ul>
              </div>

              <div class="theme-card-muted mt-4 rounded-2xl p-5">
                <p class="theme-title text-sm font-bold">Popular destinations</p>
                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                  <RouterLink to="/deals" class="rounded-full bg-white/80 px-3 py-1.5 text-brand-500 dark:bg-slate-900/70">
                    Deals
                  </RouterLink>
                  <RouterLink to="/insights" class="rounded-full bg-white/80 px-3 py-1.5 text-brand-500 dark:bg-slate-900/70">
                    Insights
                  </RouterLink>
                  <RouterLink to="/faq" class="rounded-full bg-white/80 px-3 py-1.5 text-brand-500 dark:bg-slate-900/70">
                    FAQ
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
