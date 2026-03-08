<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";
import { cartApi } from "@/api/cart";
import { ordersApi } from "@/api/orders";
import { addressesApi } from "@/api/addresses";

const router = useRouter();
const cart = useCartStore();
const auth = useAuthStore();

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
  phone: "",
});
const step = ref("address"); // address | shipping | payment
const loading = ref(false);
const error = ref(null);

onMounted(async () => {
  await cart.fetch();

  const addressRes = await addressesApi.list().catch(() => null);

  if (addressRes) {
    const addresses =
      addressRes.data?.data ?? addressRes.data ?? [];
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
    address.value.phone = auth.user.phone ?? "";
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
    const { data } = await ordersApi.place({
      store_id: cart.storeId,
    });
    // PayPal redirect URL comes back from the API
    if (data.redirect_url) {
      window.location.href = data.redirect_url;
    } else {
      router.push({
        name: "checkout.success",
        query: { order: data.order_id },
      });
    }
  } catch (e) {
    error.value = e.response?.data?.message ?? "Failed to place order.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
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

    <div class="grid gap-8 lg:grid-cols-3">
      <!-- Form area -->
      <div class="lg:col-span-2 space-y-6">
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
            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
            @submit.prevent="saveAddress"
          >
            <div>
              <label
                for="addr-first-name"
                class="mb-1 block text-xs font-medium text-slate-600"
                >First Name</label
              >
              <input
                id="addr-first-name"
                v-model="address.first_name"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div>
              <label
                for="addr-last-name"
                class="mb-1 block text-xs font-medium text-slate-600"
                >Last Name</label
              >
              <input
                id="addr-last-name"
                v-model="address.last_name"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div class="col-span-full">
              <label
                for="addr-line-one"
                class="mb-1 block text-xs font-medium text-slate-600"
                >Address Line</label
              >
              <input
                id="addr-line-one"
                v-model="address.line_one"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div>
              <label
                for="addr-city"
                class="mb-1 block text-xs font-medium text-slate-600"
                >City</label
              >
              <input
                id="addr-city"
                v-model="address.city"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div>
              <label
                for="addr-state"
                class="mb-1 block text-xs font-medium text-slate-600"
                >Province</label
              >
              <input
                id="addr-state"
                v-model="address.state"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div>
              <label
                for="addr-postcode"
                class="mb-1 block text-xs font-medium text-slate-600"
                >ZIP Code</label
              >
              <input
                id="addr-postcode"
                v-model="address.postcode"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div>
              <label
                for="addr-phone"
                class="mb-1 block text-xs font-medium text-slate-600"
                >Phone</label
              >
              <input
                id="addr-phone"
                v-model="address.phone"
                type="tel"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
            </div>
            <div class="col-span-full">
              <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-bold text-white hover:from-brand-600 hover:to-brand-700 disabled:opacity-50 transition-all"
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
          <button
            type="button"
            :disabled="!selectedShipping || loading"
            class="mt-4 w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-bold text-white hover:from-brand-600 hover:to-brand-700 disabled:opacity-50 transition-all"
            @click="selectShipping"
          >
            Continue to Payment
          </button>
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
          <button
            type="button"
            :disabled="loading"
            class="w-full rounded-xl bg-yellow-400 py-3 text-sm font-bold text-slate-900 hover:bg-yellow-300 disabled:opacity-50 transition-colors"
            @click="placeOrder"
          >
            {{ loading ? "Redirecting…" : "Pay with PayPal" }}
          </button>
        </section>
      </div>

      <!-- Order summary -->
      <aside class="rounded-2xl border border-slate-200 bg-white p-5 h-fit">
        <h2 class="mb-4 text-base font-semibold text-slate-900">
          Order Summary
        </h2>
        <ul class="divide-y text-sm">
          <li
            v-for="line in cart.cart?.lines"
            :key="line.id"
            class="flex justify-between py-2"
          >
            <span class="text-slate-600 line-clamp-1 flex-1 mr-3"
              >{{ line.purchasable?.name }} × {{ line.quantity }}</span
            >
            <span class="font-medium text-slate-900">{{
              line.sub_total?.formatted
            }}</span>
          </li>
        </ul>
        <div
          class="mt-4 border-t pt-3 flex justify-between font-bold text-slate-900"
        >
          <span>Total</span>
          <span>{{ cart.total }}</span>
        </div>
      </aside>
    </div>
  </div>
</template>
