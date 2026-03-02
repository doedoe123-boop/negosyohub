<script setup>
import { RouterView } from "vue-router";
import Navbar from "@/components/Navbar.vue";
import Footer from "@/components/Footer.vue";
import CartDrawer from "@/components/CartDrawer.vue";
import { useCartStore } from "@/stores/cart";

const cart = useCartStore();
</script>

<template>
  <div class="flex min-h-screen flex-col bg-white">
    <Navbar />

    <main class="flex-1">
      <RouterView />
    </main>

    <Footer />

    <!-- Cart drawer overlay -->
    <CartDrawer :open="cart.isOpen" @close="cart.closeDrawer" />
  </div>
</template>
