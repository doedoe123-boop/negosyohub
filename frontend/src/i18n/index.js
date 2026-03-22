import { inject, reactive, ref } from "vue";
import { localizationApi } from "@/api/localization";

const I18N_KEY = Symbol("negosyohub.i18n");
export const STORAGE_KEY = "negosyohub.locale";
let fallbackI18n = null;

const fallbackMessages = {
  en: {
    nav: {
      sectors: "Sectors",
      sellWithUs: "Sell with us",
      search: "Search…",
      signIn: "Sign in",
      register: "Register",
      account: "Account",
      signOut: "Sign out",
    },
    auth: {
      login: {
        title: "Welcome back",
        subtitle: "Sign in to your customer account",
        submit: "Sign In",
        submitting: "Signing in...",
        forgotPassword: "Forgot password?",
        invalid: "Invalid email or password.",
        sellerPortal: "Sign in at the Seller Portal",
        noAccount: "Don't have an account?",
      },
      register: {
        title: "Create your account",
        subtitle: "Join NegosyoHub - it's free",
        submit: "Create Account",
        submitting: "Creating account...",
        failed: "Registration failed.",
        haveAccount: "Already have an account?",
        signIn: "Sign in",
        sellerPrompt: "Want to sell or list properties?",
        sellerCta: "Register as a seller",
      },
      forgotPassword: {
        title: "Forgot your password?",
        subtitle: "Enter your email and we'll send you a reset link.",
        submit: "Send Reset Link",
        submitting: "Sending...",
        checkInbox: "Check your inbox",
        success: "If :email is registered, we've sent a password reset link. Be sure to check your spam folder too.",
        backToLogin: "Back to login",
        remembered: "Remember your password?",
        error: "Something went wrong. Please try again.",
      },
      resetPassword: {
        title: "Reset your password",
        subtitle: "Choose a strong new password for your account.",
        invalidTitle: "Invalid reset link",
        invalidBody: "This link is missing required information. Please request a new password reset.",
        requestNew: "Request a new link",
        submit: "Reset Password",
        submitting: "Resetting...",
        error: "Could not reset your password. The link may have expired.",
      },
      fields: {
        name: "Full name",
        email: "Email address",
        password: "Password",
        newPassword: "New password",
        confirmPassword: "Confirm password",
        confirmNewPassword: "Confirm new password",
        namePlaceholder: "Juan dela Cruz",
        emailPlaceholder: "you@example.com",
        passwordPlaceholder: "At least 8 characters",
        confirmPasswordPlaceholder: "Re-enter your password",
        newPasswordPlaceholder: "Min. 8 chars, upper, lower & number",
        confirmNewPasswordPlaceholder: "Repeat your new password",
      },
    },
    cart: {
      title: "Shopping Cart",
      empty: "Your cart is empty",
      browse: "Browse stores",
      orderSummary: "Order summary",
      subtotal: "Subtotal",
      delivery: "Delivery",
      total: "Total",
      proceed: "Proceed to Checkout",
    },
    checkout: {
      address: "Address",
      shipping: "Shipping",
      payment: "Payment",
      orderSummary: "Order Summary",
      total: "Total",
      leaveCheckout: "Leave Checkout?",
      continueCheckout: "Continue Checkout",
    },
    orders: {
      myOrders: "My Orders",
      noOrders: "No orders yet.",
      view: "View",
      deliveryProgress: "Delivery Progress",
    },
  },
  fil: {
    nav: {
      sectors: "Mga Sector",
      sellWithUs: "Magbenta sa amin",
      search: "Maghanap…",
      signIn: "Mag-sign in",
      register: "Mag-register",
      account: "Account",
      signOut: "Mag-sign out",
    },
    auth: {
      login: {
        title: "Maligayang pagbabalik",
        subtitle: "Mag-sign in sa iyong customer account",
        submit: "Mag-sign In",
        submitting: "Nagsa-sign in...",
        forgotPassword: "Nakalimutan ang password?",
        invalid: "Mali ang email o password.",
        sellerPortal: "Mag-sign in sa Seller Portal",
        noAccount: "Wala ka pang account?",
      },
      register: {
        title: "Gumawa ng account",
        subtitle: "Sumali sa NegosyoHub nang libre",
        submit: "Gumawa ng Account",
        submitting: "Gumagawa ng account...",
        failed: "Hindi natuloy ang registration.",
        haveAccount: "May account ka na ba?",
        signIn: "Mag-sign in",
        sellerPrompt: "Gusto mo bang magbenta o maglista ng properties?",
        sellerCta: "Mag-register bilang seller",
      },
      forgotPassword: {
        title: "Nakalimutan ang password?",
        subtitle: "Ilagay ang iyong email at padadalhan ka namin ng reset link.",
        submit: "Ipadala ang Reset Link",
        submitting: "Ipinapadala...",
        checkInbox: "Tingnan ang iyong inbox",
        success: "Kung rehistrado ang :email, nagpadala na kami ng password reset link. Tumingin din sa spam folder.",
        backToLogin: "Bumalik sa login",
        remembered: "Naalala mo na ba ang password mo?",
        error: "May nangyaring mali. Pakisubukang muli.",
      },
      resetPassword: {
        title: "I-reset ang iyong password",
        subtitle: "Pumili ng matibay na bagong password para sa iyong account.",
        invalidTitle: "Hindi wasto ang reset link",
        invalidBody: "Kulang ang impormasyong nasa link na ito. Mangyaring humiling ng panibagong password reset.",
        requestNew: "Humiling ng bagong link",
        submit: "I-reset ang Password",
        submitting: "Nire-reset...",
        error: "Hindi ma-reset ang iyong password. Maaaring paso na ang link.",
      },
      fields: {
        name: "Buong pangalan",
        email: "Email address",
        password: "Password",
        newPassword: "Bagong password",
        confirmPassword: "Kumpirmahin ang password",
        confirmNewPassword: "Kumpirmahin ang bagong password",
        namePlaceholder: "Juan dela Cruz",
        emailPlaceholder: "you@example.com",
        passwordPlaceholder: "Hindi bababa sa 8 character",
        confirmPasswordPlaceholder: "Ilagay muli ang password",
        newPasswordPlaceholder: "Min. 8 char, upper, lower at number",
        confirmNewPasswordPlaceholder: "Ulitin ang bagong password",
      },
    },
    cart: {
      title: "Shopping Cart",
      empty: "Wala pang laman ang cart mo",
      browse: "Mag-browse ng stores",
      orderSummary: "Buod ng order",
      subtotal: "Subtotal",
      delivery: "Delivery",
      total: "Kabuuan",
      proceed: "Mag-checkout",
    },
    checkout: {
      address: "Address",
      shipping: "Shipping",
      payment: "Bayad",
      orderSummary: "Buod ng Order",
      total: "Kabuuan",
      leaveCheckout: "Umalis sa Checkout?",
      continueCheckout: "Magpatuloy sa Checkout",
    },
    orders: {
      myOrders: "Aking Orders",
      noOrders: "Wala ka pang order.",
      view: "Tingnan",
      deliveryProgress: "Takbo ng Delivery",
    },
  },
};

function resolveDefaultLocale() {
  const savedLocale = localStorage.getItem(STORAGE_KEY);
  if (savedLocale) {
    return savedLocale;
  }

  const browserLocale = navigator.language?.toLowerCase() ?? "en";

  return browserLocale.startsWith("fil") || browserLocale.startsWith("tl")
    ? "fil"
    : "en";
}

function lookup(messages, locale, key) {
  return key.split(".").reduce((value, segment) => value?.[segment], messages[locale]);
}

function mergeMessages(target, source) {
  Object.entries(source).forEach(([key, value]) => {
    if (value && typeof value === "object" && !Array.isArray(value)) {
      target[key] ??= {};
      mergeMessages(target[key], value);
      return;
    }

    target[key] = value;
  });
}

export function createAppI18n() {
  const locale = ref(resolveDefaultLocale());
  const availableLocales = ref([
    { code: "en", name: "English", is_default: true },
    { code: "fil", name: "Filipino", is_default: false },
  ]);
  const messages = reactive(structuredClone(fallbackMessages));

  const syncDom = () => {
    document.documentElement.lang = locale.value;
  };

  syncDom();

  const hydrateLocale = async (nextLocale) => {
    const { data } = await localizationApi.catalog(nextLocale);
    availableLocales.value = data.available_locales ?? availableLocales.value;

    messages[nextLocale] ??= {};
    mergeMessages(messages[nextLocale], data.messages ?? {});
  };

  const setLocale = async (nextLocale) => {
    locale.value = nextLocale;
    localStorage.setItem(STORAGE_KEY, nextLocale);
    syncDom();

    try {
      await hydrateLocale(nextLocale);
    } catch {
      // Keep fallback messages if the backend catalog is unavailable.
    }
  };

  const interpolate = (value, replace = {}) => {
    return Object.entries(replace).reduce((result, [replaceKey, replaceValue]) => {
      return result.replaceAll(`:${replaceKey}`, String(replaceValue));
    }, value);
  };

  const t = (key, replace = {}, fallback = null) => {
    const value =
      lookup(messages, locale.value, key) ??
      lookup(messages, "en", key) ??
      fallback ??
      key;

    return typeof value === "string" ? interpolate(value, replace) : value;
  };

  setLocale(locale.value);

  return {
    locale,
    locales: availableLocales,
    setLocale,
    t,
  };
}

export function installAppI18n(app, i18n) {
  app.provide(I18N_KEY, i18n);
  app.config.globalProperties.$t = i18n.t;
}

export function useAppI18n() {
  fallbackI18n ??= createAppI18n();

  return inject(I18N_KEY, fallbackI18n);
}
