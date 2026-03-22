<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";
import { LockClosedIcon, ShieldCheckIcon } from "@heroicons/vue/24/outline";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";
import { ordersApi } from "@/api/orders";
import { cartApi } from "@/api/cart";
import { paypalApi } from "@/api/paypal";
import { addressesApi } from "@/api/addresses";
import CouponInput from "@/components/CouponInput.vue";
import { useAppI18n } from "@/i18n";

const router = useRouter();
const cart = useCartStore();
const auth = useAuthStore();
const { t } = useAppI18n();

// Guard: redirect to cart if empty
const cartReady = ref(false);

const shippingOptions = ref([]);
const selectedShipping = ref(null);
const address = ref({
  first_name: "",
  last_name: "",
  line_one: "",
  city: "",
  state: "",
  postcode: "",
  country: "PH",
  contact_phone: "",
  contact_email: "",
});
const step = ref("address"); // address | shipping | payment
const loading = ref(false);
const error = ref(null);
const showLeaveModal = ref(false);
const appliedCoupon = ref(null);
const selectedPaymentMethod = ref("paypal");
const paymentOptions = [
  {
    value: "paypal",
    label: "PayPal",
    description: "Pay securely online before we place your order.",
  },
  {
    value: "cash_on_delivery",
    label: "Cash on Delivery",
    description: "Pay when your order arrives.",
  },
];

function onCouponApplied(coupon) {
  appliedCoupon.value = coupon;
  cart.fetch();
}

function onCouponRemoved() {
  appliedCoupon.value = null;
  cart.fetch();
}

function goBack() {
  if (step.value === "shipping") {
    step.value = "address";
  } else if (step.value === "payment") {
    step.value = "shipping";
  }
}

function confirmLeave() {
  showLeaveModal.value = false;
  router.push("/cart");
}

onMounted(async () => {
  await cart.fetch();

  if (!cart.lineCount) {
    router.replace("/cart");
    return;
  }
  cartReady.value = true;

  const addressRes = await addressesApi.list().catch(() => null);

  if (addressRes) {
    const addresses = addressRes.data?.data ?? addressRes.data ?? [];
    const def = addresses.find((a) => a.is_default) ?? addresses[0];

    if (def) {
      address.value.line_one = def.line1 ?? "";
      address.value.city = def.city ?? "";
      address.value.state = def.province ?? "";
      address.value.postcode = def.postal_code ?? "";
    }
  }

  // Pre-fill name and phone from the authenticated user profile
  if (auth.user) {
    const parts = (auth.user.name ?? "").trim().split(/\s+/);
    address.value.first_name = parts[0] ?? "";
    address.value.last_name = parts.slice(1).join(" ") ?? "";
    address.value.contact_phone = auth.user.phone ?? "";
    address.value.contact_email = auth.user.email ?? "";
  }
});

async function saveAddress() {
  loading.value = true;
  error.value = null;
  try {
    await cartApi.setAddress(address.value);

    // Fetch shipping options now that the address is on the cart
    const { data } = await cartApi.shippingOptions();
    shippingOptions.value = data ?? [];

    step.value = "shipping";
  } catch (e) {
    error.value = "Failed to save address.";
  } finally {
    loading.value = false;
  }
}

async function selectShipping() {
  if (!selectedShipping.value) return;
  loading.value = true;
  try {
    await cartApi.setShippingOption(selectedShipping.value);
    step.value = "payment";
  } catch {
    error.value = "Failed to set shipping.";
  } finally {
    loading.value = false;
  }
}

async function placeOrder() {
  loading.value = true;
  error.value = null;
  try {
    if (selectedPaymentMethod.value === "cash_on_delivery") {
      const { data } = await ordersApi.place({
        store_id: cart.storeId,
        payment_method: "cash_on_delivery",
      });

      cart.reset();

      await router.push({
        path: "/checkout/success",
        query: { order: data.order_id },
      });

      return;
    }

    const { data } = await paypalApi.createOrder();

    if (!data.approve_url) {
      error.value = "Failed to initiate PayPal payment. Please try again.";
      return;
    }

    sessionStorage.setItem("paypal_store_id", cart.storeId);
    window.location.href = data.approve_url;
  } catch (e) {
    error.value = e.response?.data?.message ?? "Failed to place order.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div v-if="cartReady">
    <nav aria-label="Checkout steps" class="mb-8 flex items-center gap-0">
      <template
        v-for="(label, idx) in [t('checkout.address'), t('checkout.shipping'), t('checkout.payment')]"
        :key="label"
      >
        <div class="flex items-center gap-2">
          <span
            class="flex size-7 items-center justify-center rounded-full text-xs font-bold transition-colors"
            :class="
              step === ['address', 'shipping', 'payment'][idx]
                ? 'bg-brand-500 text-white shadow-sm'
                : ['address', 'shipping', 'payment'].indexOf(step) > idx
                  ? 'bg-brand-100 text-brand-600'
                  : 'theme-card-muted theme-copy'
            "
            >{{ idx + 1 }}</span
          >
          <span
            class="text-sm font-medium transition-colors"
            :class="
              step === ['address', 'shipping', 'payment'][idx]
                ? 'theme-title'
                : 'theme-copy'
            "
            >{{ label }}</span
          >
        </div>
        <div
          v-if="idx < 2"
          class="theme-divider-soft mx-3 h-px flex-1 border-t"
        />
      </template>
    </nav>

    <div class="flex flex-col gap-8 lg:grid lg:grid-cols-12 lg:items-start">
      <!-- Form area -->
      <div class="space-y-6 lg:col-span-8 xl:col-span-8">
        <p
          v-if="error"
          class="rounded-lg bg-red-500/10 px-3 py-2 text-sm text-red-400"
        >
          {{ error }}
        </p>

        <section
          v-if="step === 'address'"
          class="theme-card rounded-2xl p-6"
        >
          <h2 class="theme-title mb-4 text-lg font-bold">
            Delivery Address
          </h2>
          <form
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 mt-2"
            @submit.prevent="saveAddress"
          >
            <div class="relative">
              <input
                id="addr-first-name"
                v-model="address.first_name"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-first-name"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                First Name
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-last-name"
                v-model="address.last_name"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-last-name"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Last Name
              </label>
            </div>
            <div class="relative col-span-full">
              <input
                id="addr-line-one"
                v-model="address.line_one"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-line-one"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Address Line
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-city"
                v-model="address.city"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-city"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                City
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-state"
                v-model="address.state"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-state"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Province
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-postcode"
                v-model="address.postcode"
                required
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-postcode"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                ZIP Code
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-phone"
                v-model="address.contact_phone"
                type="tel"
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-phone"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Phone
              </label>
            </div>
            <div class="relative">
              <input
                id="addr-email"
                v-model="address.contact_email"
                type="email"
                placeholder=" "
                class="theme-input peer block w-full appearance-none rounded-lg px-3 pb-2.5 pt-4 text-sm focus:outline-none"
              />
              <label
                for="addr-email"
                class="theme-input-label pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform px-1 text-sm duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Email
              </label>
            </div>
            <div class="col-span-full mt-2 flex gap-3">
              <button
                type="button"
                class="btn-secondary rounded-xl px-5 py-3 text-sm font-medium"
                @click="showLeaveModal = true"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="flex-1 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-bold text-white transition-all hover:from-brand-600 hover:to-brand-700 disabled:opacity-50"
              >
                Continue to Shipping
              </button>
            </div>
          </form>
        </section>

        <!-- Step: Shipping -->
        <section
          v-else-if="step === 'shipping'"
          class="theme-card rounded-2xl p-6"
        >
          <h2 class="theme-title mb-4 text-lg font-bold">Shipping Method</h2>
          <div
            v-if="shippingOptions.length === 0"
            class="theme-copy text-sm"
          >
            No shipping options available for your address.
          </div>
          <div v-else class="space-y-3">
            <label
              v-for="option in shippingOptions"
              :key="option.id"
              class="theme-card-muted flex cursor-pointer items-center gap-3 rounded-xl p-4 transition-colors hover:bg-[var(--color-surface)]"
              :class="{
                'border-brand-400 bg-brand-500/10': selectedShipping === option.id,
              }"
            >
              <input
                type="radio"
                v-model="selectedShipping"
                :value="option.id"
                class="text-brand-500"
              />
              <div class="flex-1">
                <p class="theme-title text-sm font-semibold">
                  {{ option.name }}
                </p>
                <p class="theme-copy text-xs">{{ option.description }}</p>
              </div>
              <span class="theme-title text-sm font-bold">{{
                option.price?.formatted
              }}</span>
            </label>
          </div>
          <div class="mt-4 flex gap-3">
            <button
              type="button"
              class="btn-secondary rounded-xl px-5 py-3 text-sm font-medium"
              @click="goBack"
            >
              ← Back
            </button>
            <button
              type="button"
              :disabled="!selectedShipping || loading"
              class="flex-1 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-bold text-white transition-all hover:from-brand-600 hover:to-brand-700 disabled:opacity-50"
              @click="selectShipping"
            >
              Continue to Payment
            </button>
          </div>
        </section>

        <!-- Step: Payment -->
        <section
          v-else-if="step === 'payment'"
          class="theme-card rounded-2xl p-6"
        >
          <h2 class="theme-title mb-4 text-lg font-bold">Payment</h2>
          <p class="theme-copy mb-6 text-sm">
            Choose how you want to pay for this order.
          </p>
          <div class="mb-6 space-y-3">
            <label
              v-for="option in paymentOptions"
              :key="option.value"
              class="theme-card-muted flex cursor-pointer items-start gap-3 rounded-xl p-4 transition-colors hover:bg-[var(--color-surface)]"
              :class="{
                'border-brand-400 bg-brand-500/10': selectedPaymentMethod === option.value,
              }"
            >
              <input
                v-model="selectedPaymentMethod"
                type="radio"
                :value="option.value"
                class="mt-0.5 text-brand-500"
              />
              <div class="flex-1">
                <p class="theme-title text-sm font-semibold">
                  {{ option.label }}
                </p>
                <p class="theme-copy text-xs">
                  {{ option.description }}
                </p>
              </div>
            </label>
          </div>
          <div class="flex gap-3 mb-6">
            <button
              type="button"
              class="btn-secondary rounded-xl px-5 py-3 text-sm font-medium"
              @click="goBack"
            >
              ← Back
            </button>
            <button
              type="button"
              :disabled="loading"
              class="flex-1 rounded-xl py-3 text-sm font-bold text-white transition-all active:scale-[0.98] disabled:opacity-50 shadow-md cursor-pointer"
              :class="
                selectedPaymentMethod === 'paypal'
                  ? 'bg-[#0551b5] hover:bg-[#1161CA]'
                  : 'bg-brand-600 hover:bg-brand-700'
              "
              @click="placeOrder"
            >
              {{
                loading
                  ? selectedPaymentMethod === "paypal"
                    ? "Redirecting…"
                    : "Placing order…"
                  : selectedPaymentMethod === "paypal"
                    ? "Pay with PayPal"
                    : "Place COD Order"
              }}
            </button>
          </div>

          <p
            v-if="selectedPaymentMethod === 'cash_on_delivery'"
            class="mb-5 rounded-xl bg-emerald-500/12 px-4 py-3 text-sm text-emerald-300"
          >
            Cash on Delivery is available for this e-commerce order. Pay when
            your order arrives.
          </p>

          <div
            class="theme-copy theme-divider-soft flex items-center justify-center gap-6 border-t pt-5 text-sm font-medium"
          >
            <div class="flex items-center gap-1.5">
              <LockClosedIcon class="h-4 w-4 text-emerald-600" />
              <span>Secure SSL</span>
            </div>
            <div class="flex items-center gap-1.5">
              <ShieldCheckIcon class="h-4 w-4 text-emerald-600" />
              <span>Money Back Guarantee</span>
            </div>
          </div>
        </section>
      </div>

      <!-- Order summary -->
      <aside
        class="theme-card sticky top-6 h-fit w-full rounded-2xl p-6 lg:col-span-4 xl:col-span-4"
      >
        <h2 class="theme-title mb-4 text-lg font-bold">{{ t("checkout.orderSummary") }}</h2>
        <ul class="divide-y divide-[var(--color-border)] text-sm">
          <li
            v-for="line in cart.cart?.lines"
            :key="line.id"
            class="flex justify-between py-3"
          >
            <span
              class="theme-copy line-clamp-2 mr-4 flex-1 leading-relaxed"
              >{{ line.purchasable?.name }} × {{ line.quantity }}</span
            >
            <span class="theme-title font-semibold">{{
              line.sub_total?.formatted
            }}</span>
          </li>
        </ul>
        <div class="theme-divider mt-4 border-t pt-4">
          <CouponInput @applied="onCouponApplied" @removed="onCouponRemoved" />
        </div>
        <div class="theme-copy mt-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span>Subtotal</span>
            <span class="theme-title font-medium">{{ cart.originalTotal }}</span>
          </div>
          <div
            v-if="cart.appliedCoupon"
            class="flex justify-between text-emerald-500"
          >
            <span>{{ cart.appliedCoupon.code }}</span>
            <span>-{{ cart.discountTotal }}</span>
          </div>
          <div class="flex justify-between">
            <span>Shipping</span>
            <span>{{ cart.cart?.shipping_total?.formatted ?? "Calculated after address" }}</span>
          </div>
          <div class="flex justify-between">
            <span>Tax</span>
            <span>{{ cart.cart?.tax_total?.formatted ?? "₱0.00" }}</span>
          </div>
        </div>
        <div
          class="theme-divider mt-6 flex items-center justify-between border-t pt-5"
        >
          <span class="theme-title text-base font-semibold">{{ t("checkout.total") }}</span>
          <span class="theme-title text-2xl font-black tracking-tight">{{
            cart.total
          }}</span>
        </div>
      </aside>
    </div>
  </div>

  <!-- Leave checkout warning modal -->
  <Teleport to="body">
    <div
      v-if="showLeaveModal"
      class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="showLeaveModal = false"
    >
      <div
        class="theme-modal w-full max-w-sm rounded-2xl p-6"
        @click.stop
      >
        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-full bg-yellow-500/12"
          >
            <ExclamationTriangleIcon class="size-5 text-yellow-300" />
          </div>
          <h3 class="theme-title text-lg font-bold">{{ t("checkout.leaveCheckout") }}</h3>
        </div>
        <p class="theme-copy mb-6 text-sm">
          Your items will stay in your cart, but any address and shipping
          selections will need to be re-entered.
        </p>
        <div class="flex justify-end gap-3">
          <button
            class="btn-secondary rounded-xl px-4 py-2 text-sm font-medium"
            @click="showLeaveModal = false"
          >
            {{ t("checkout.continueCheckout") }}
          </button>
          <button
            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-700"
            @click="confirmLeave"
          >
            Leave
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
