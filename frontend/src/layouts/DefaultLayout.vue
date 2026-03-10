<script setup>
import { onMounted } from "vue";
import { RouterView } from "vue-router";
import Navbar from "@/components/Navbar.vue";
import Footer from "@/components/Footer.vue";
import CartDrawer from "@/components/CartDrawer.vue";
import AnnouncementBar from "@/components/AnnouncementBar.vue";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";

const cart = useCartStore();
const auth = useAuthStore();

// Rehydrate cart from the server on every fresh page load.
// The router's beforeEach guard has already resolved auth by the time
// this component mounts, so auth.isLoggedIn is reliable here.
onMounted(() => {
  if (auth.isLoggedIn) cart.fetch();
});
</script>

<template>
  <div class="flex min-h-screen flex-col bg-slate-50">
    <AnnouncementBar />
    <Navbar />

    <main class="flex-1">
      <RouterView />
    </main>

    <Footer />

    <!-- Cart drawer overlay -->
    <CartDrawer :open="cart.isOpen" @close="cart.closeDrawer" />
  </div>
</template>
