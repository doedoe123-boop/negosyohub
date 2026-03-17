<script setup>
import { computed, onMounted } from "vue";
import { RouterView } from "vue-router";
import { useHead } from "@unhead/vue";
import Navbar from "@/components/Navbar.vue";
import Footer from "@/components/Footer.vue";
import CartDrawer from "@/components/CartDrawer.vue";
import AnnouncementBar from "@/components/AnnouncementBar.vue";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";
import { useSeoStore } from "@/stores/seo";

const cart = useCartStore();
const auth = useAuthStore();
const seo = useSeoStore();

// Rehydrate cart from the server on every fresh page load.
// The router's beforeEach guard has already resolved auth by the time
// this component mounts, so auth.isLoggedIn is reliable here.
onMounted(async () => {
  if (auth.isLoggedIn) cart.fetch();

  await seo.fetchSettings();

  // Set the site-wide title template so every page gets "Title | SiteName".
  useHead({
    titleTemplate: (title) =>
      title ? `${title} | ${seo.siteName}` : seo.siteName,
  });

  // Google Analytics 4
  if (seo.googleAnalyticsId) {
    const script = document.createElement("script");
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${seo.googleAnalyticsId}`;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    function gtag(...args) {
      window.dataLayer.push(args);
    }
    window.gtag = gtag;
    gtag("js", new Date());
    gtag("config", seo.googleAnalyticsId);
  }

  // Google Tag Manager
  if (seo.googleTagManagerId) {
    const gtmId = seo.googleTagManagerId;
    // GTM script tag
    const script = document.createElement("script");
    script.innerHTML = `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${gtmId}');`;
    document.head.appendChild(script);

    // GTM noscript iframe (injected into body)
    const noscript = document.createElement("noscript");
    noscript.innerHTML = `<iframe src="https://www.googletagmanager.com/ns.html?id=${gtmId}" height="0" width="0" style="display:none;visibility:hidden"></iframe>`;
    document.body.insertBefore(noscript, document.body.firstChild);
  }

  // Meta (Facebook) Pixel
  if (seo.facebookPixelId) {
    const pixelId = seo.facebookPixelId;
    window.fbq =
      window.fbq ||
      function (...args) {
        (window.fbq.q = window.fbq.q || []).push(args);
      };
    window._fbq = window._fbq || window.fbq;
    window.fbq.push = window.fbq;
    window.fbq.loaded = true;
    window.fbq.version = "2.0";
    window.fbq.queue = [];

    const script = document.createElement("script");
    script.async = true;
    script.src = "https://connect.facebook.net/en_US/fbevents.js";
    document.head.appendChild(script);

    window.fbq("init", pixelId);
    window.fbq("track", "PageView");
  }
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
