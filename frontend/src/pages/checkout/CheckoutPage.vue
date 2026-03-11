<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";
import { LockClosedIcon, ShieldCheckIcon } from "@heroicons/vue/24/outline";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";
import { cartApi } from "@/api/cart";
import { paypalApi } from "@/api/paypal";
import { addressesApi } from "@/api/addresses";
import CouponInput from "@/components/CouponInput.vue";

const router = useRouter();
const cart = useCartStore();
const auth = useAuthStore();

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

function onCouponApplied(coupon) {
  appliedCoupon.value = coupon;
}

function onCouponRemoved() {
  appliedCoupon.value = null;
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
    // Step 1: Create PayPal order from current cart
    const { data } = await paypalApi.createOrder();

    if (data.approve_url) {
      // Save store_id so we can use it after PayPal redirect
      sessionStorage.setItem("paypal_store_id", cart.storeId);
      // Redirect customer to PayPal for approval
      window.location.href = data.approve_url;
    } else {
      error.value = "Failed to initiate PayPal payment. Please try again.";
    }
  } catch (e) {
    error.value = e.response?.data?.message ?? "Failed to place order.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div v-if="cartReady">
    <!-- Step progress stepper -->
    <nav aria-label="Checkout steps" class="mb-8 flex items-center gap-0">
      <template
        v-for="(label, idx) in ['Address', 'Shipping', 'Payment']"
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
                  : 'bg-slate-100 text-slate-400'
            "
            >{{ idx + 1 }}</span
          >
          <span
            class="text-sm font-medium transition-colors"
            :class="
              step === ['address', 'shipping', 'payment'][idx]
                ? 'text-slate-900'
                : 'text-slate-400'
            "
            >{{ label }}</span
          >
        </div>
        <div v-if="idx < 2" class="mx-3 h-px flex-1 bg-slate-200" />
      </template>
    </nav>

    <div class="flex flex-col gap-8 lg:grid lg:grid-cols-12 lg:items-start">
      <!-- Form area -->
      <div class="space-y-6 lg:col-span-8 xl:col-span-8">
        <p
          v-if="error"
          class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"
        >
          {{ error }}
        </p>

        <!-- Step: Address -->
        <section
          v-if="step === 'address'"
          class="rounded-2xl border border-slate-200 bg-white p-6"
        >
          <h2 class="mb-4 text-lg font-bold text-slate-900">
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-first-name"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-last-name"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-line-one"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-city"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-state"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-postcode"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-phone"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
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
                class="peer block w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-3 pb-2.5 pt-4 text-sm text-slate-900 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <label
                for="addr-email"
                class="pointer-events-none absolute left-2 top-2 z-10 origin-[0] -translate-y-4 scale-75 transform bg-white px-1 text-sm text-slate-500 duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:scale-100 peer-focus:top-2 peer-focus:-translate-y-4 peer-focus:scale-75 peer-focus:px-1 peer-focus:text-brand-500"
              >
                Email
              </label>
            </div>
            <div class="col-span-full mt-2 flex gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
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
          class="rounded-2xl border border-slate-200 bg-white p-6"
        >
          <h2 class="mb-4 text-lg font-bold text-slate-900">Shipping Method</h2>
          <div
            v-if="shippingOptions.length === 0"
            class="text-sm text-slate-400"
          >
            No shipping options available for your address.
          </div>
          <div v-else class="space-y-3">
            <label
              v-for="option in shippingOptions"
              :key="option.id"
              class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors hover:bg-slate-50"
              :class="{
                'border-brand-400 bg-brand-50': selectedShipping === option.id,
              }"
            >
              <input
                type="radio"
                v-model="selectedShipping"
                :value="option.id"
                class="text-brand-500"
              />
              <div class="flex-1">
                <p class="text-sm font-semibold text-slate-800">
                  {{ option.name }}
                </p>
                <p class="text-xs text-slate-500">{{ option.description }}</p>
              </div>
              <span class="text-sm font-bold text-slate-900">{{
                option.price?.formatted
              }}</span>
            </label>
          </div>
          <div class="mt-4 flex gap-3">
            <button
              type="button"
              class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
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
          class="rounded-2xl border border-slate-200 bg-white p-6"
        >
          <h2 class="mb-4 text-lg font-bold text-slate-900">Payment</h2>
          <p class="mb-6 text-sm text-slate-500">
            You will be redirected to PayPal to complete your payment securely.
          </p>
          <div class="flex gap-3 mb-6">
            <button
              type="button"
              class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="goBack"
            >
              ← Back
            </button>
            <button
              type="button"
              :disabled="loading"
              class="flex-1 rounded-xl bg-[#0551b5] py-3 text-sm font-bold text-white transition-all hover:bg-[#1161CA] active:scale-[0.98] disabled:opacity-50 shadow-md cursor-pointer"
              @click="placeOrder"
            >
              {{ loading ? "Redirecting…" : "Pay with PayPal" }}
            </button>
          </div>

          <div
            class="flex items-center justify-center gap-6 border-t border-slate-100 pt-5 text-sm font-medium text-slate-600"
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
        class="sticky top-6 h-fit w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-4 xl:col-span-4"
      >
        <h2 class="mb-4 text-lg font-bold text-slate-900">Order Summary</h2>
        <ul class="divide-y text-sm">
          <li
            v-for="line in cart.cart?.lines"
            :key="line.id"
            class="flex justify-between py-3"
          >
            <span
              class="text-slate-600 line-clamp-2 flex-1 mr-4 leading-relaxed"
              >{{ line.purchasable?.name }} × {{ line.quantity }}</span
            >
            <span class="font-semibold text-slate-900">{{
              line.sub_total?.formatted
            }}</span>
          </li>
        </ul>
        <div class="mt-4 border-t pt-4">
          <CouponInput @applied="onCouponApplied" @removed="onCouponRemoved" />
        </div>
        <div
          class="mt-6 flex items-center justify-between border-t border-slate-200 pt-5"
        >
          <span class="text-base font-semibold text-slate-900">Total</span>
          <span class="text-2xl font-black tracking-tight text-slate-900">{{
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
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="showLeaveModal = false"
    >
      <div
        class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
        @click.stop
      >
        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-full bg-yellow-100"
          >
            <ExclamationTriangleIcon class="size-5 text-yellow-600" />
          </div>
          <h3 class="text-lg font-bold text-slate-900">Leave Checkout?</h3>
        </div>
        <p class="mb-6 text-sm text-slate-600">
          Your items will stay in your cart, but any address and shipping
          selections will need to be re-entered.
        </p>
        <div class="flex justify-end gap-3">
          <button
            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
            @click="showLeaveModal = false"
          >
            Continue Checkout
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
