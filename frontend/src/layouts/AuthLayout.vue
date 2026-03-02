<script setup>
import { RouterView } from "vue-router";
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="mb-8 text-center">
        <a href="/" class="text-2xl font-bold text-brand-600">NegosyoHub</a>
      </div>

      <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
        <RouterView />
      </div>
    </div>
  </div>
</template>
