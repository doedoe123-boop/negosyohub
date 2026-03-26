<script setup>
import { RouterView } from "vue-router";
import { Analytics } from "@vercel/analytics";
</script>

<template>
  <Analytics />
  <RouterView />
</template>
