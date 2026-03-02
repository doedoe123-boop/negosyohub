<script setup>
import { RouterView } from "vue-router";
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Minimal header — no cart/nav links to reduce abandonment -->
    <header class="border-b bg-white px-6 py-4">
      <a href="/" class="text-xl font-bold text-brand-600">NegosyoHub</a>
      <span class="ml-2 text-sm text-gray-500">Checkout</span>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10">
      <RouterView />
    </main>
  </div>
</template>
