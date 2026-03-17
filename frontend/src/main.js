import { createApp } from "vue";
import { createPinia } from "pinia";
import { createUnhead, headSymbol } from "@unhead/vue";
import router from "./router";
import App from "./App.vue";
import { useCartStore } from "@/stores/cart";
import "./style.css";

const app = createApp(App);
const pinia = createPinia();
const head = createUnhead();

app.use(pinia);
app.use(router);
app.provide(headSymbol, head);

// When a 401 fires mid-session (token expired), the API client emits this
// event. Only redirect to login if the user is on a protected page —
// public pages (homepage, stores, properties) should NOT bounce to login.
window.addEventListener("auth:unauthenticated", async () => {
  const { useAuthStore } = await import("@/stores/auth");
  const auth = useAuthStore();
  auth.user = null;

  const currentRoute = router.currentRoute.value;
  const isProtected =
    currentRoute.meta?.requiresAuth ||
    currentRoute.matched.some((r) => r.meta?.requiresAuth);

  if (isProtected) {
    useCartStore().reset();
    router.push({
      name: "auth.login",
      query: { redirect: currentRoute.fullPath },
    });
  }
});

app.mount("#app");
