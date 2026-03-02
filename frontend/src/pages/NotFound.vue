<script setup>
import { RouterLink } from "vue-router";
</script>

<template>
  <div
    class="flex min-h-screen flex-col items-center justify-center text-center"
  >
    <p class="text-6xl font-extrabold text-brand-400">404</p>
    <h1 class="mt-4 text-2xl font-bold text-gray-900">Page not found</h1>
    <p class="mt-2 text-gray-500">The page you're looking for doesn't exist.</p>
    <RouterLink
      to="/"
      class="mt-6 rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600 transition-colors"
    >
      Back to Home
    </RouterLink>
  </div>
</template>
