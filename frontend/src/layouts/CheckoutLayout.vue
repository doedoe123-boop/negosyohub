<script setup>
import { RouterView } from "vue-router";
</script>

<template>
  <div class="theme-page min-h-screen transition-colors">
    <header class="theme-surface theme-divider border-x-0 border-t-0 px-6 py-4 transition-colors">
      <a href="/" class="text-xl font-bold text-brand-600">NegosyoHub</a>
      <span class="theme-copy ml-2 text-sm">Checkout</span>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10">
      <RouterView />
    </main>
  </div>
</template>
