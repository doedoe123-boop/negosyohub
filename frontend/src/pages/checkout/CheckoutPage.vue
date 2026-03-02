<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/stores/cart";
import { cartApi } from "@/api/cart";
import { ordersApi } from "@/api/orders";

const router = useRouter();
const cart = useCartStore();

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
  try {
    const { data } = await cartApi.shippingOptions();
    shippingOptions.value = data;
  } catch {
    shippingOptions.value = [];
  }
});

async function saveAddress() {
  loading.value = true;
  error.value = null;
  try {
    await cartApi.setAddress(address.value);
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
    const { data } = await ordersApi.place({ payment_method: "paypal" });
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
        class="rounded-2xl border bg-white p-6"
      >
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
          Delivery Address
        </h2>
        <form class="grid grid-cols-2 gap-4" @submit.prevent="saveAddress">
          <div>
            <label
              for="addr-first-name"
              class="mb-1 block text-xs font-medium text-gray-600"
              >First Name</label
            >
            <input
              id="addr-first-name"
              v-model="address.first_name"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label
              for="addr-last-name"
              class="mb-1 block text-xs font-medium text-gray-600"
              >Last Name</label
            >
            <input
              id="addr-last-name"
              v-model="address.last_name"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div class="col-span-2">
            <label
              for="addr-line-one"
              class="mb-1 block text-xs font-medium text-gray-600"
              >Address Line</label
            >
            <input
              id="addr-line-one"
              v-model="address.line_one"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label
              for="addr-city"
              class="mb-1 block text-xs font-medium text-gray-600"
              >City</label
            >
            <input
              id="addr-city"
              v-model="address.city"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label
              for="addr-state"
              class="mb-1 block text-xs font-medium text-gray-600"
              >Province</label
            >
            <input
              id="addr-state"
              v-model="address.state"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label
              for="addr-postcode"
              class="mb-1 block text-xs font-medium text-gray-600"
              >ZIP Code</label
            >
            <input
              id="addr-postcode"
              v-model="address.postcode"
              required
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label
              for="addr-phone"
              class="mb-1 block text-xs font-medium text-gray-600"
              >Phone</label
            >
            <input
              id="addr-phone"
              v-model="address.phone"
              type="tel"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div class="col-span-2">
            <button
              type="submit"
              :disabled="loading"
              class="w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50 transition-colors"
            >
              Continue to Shipping
            </button>
          </div>
        </form>
      </section>

      <!-- Step: Shipping -->
      <section
        v-else-if="step === 'shipping'"
        class="rounded-2xl border bg-white p-6"
      >
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
          Shipping Method
        </h2>
        <div v-if="shippingOptions.length === 0" class="text-sm text-gray-400">
          No shipping options available for your address.
        </div>
        <div v-else class="space-y-3">
          <label
            v-for="option in shippingOptions"
            :key="option.id"
            class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 hover:bg-gray-50"
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
              <p class="text-sm font-medium text-gray-800">{{ option.name }}</p>
              <p class="text-xs text-gray-500">{{ option.description }}</p>
            </div>
            <span class="text-sm font-semibold text-gray-900">{{
              option.price?.formatted
            }}</span>
          </label>
        </div>
        <button
          type="button"
          :disabled="!selectedShipping || loading"
          class="mt-4 w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50 transition-colors"
          @click="selectShipping"
        >
          Continue to Payment
        </button>
      </section>

      <!-- Step: Payment -->
      <section
        v-else-if="step === 'payment'"
        class="rounded-2xl border bg-white p-6"
      >
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Payment</h2>
        <p class="mb-6 text-sm text-gray-500">
          You will be redirected to PayPal to complete your payment securely.
        </p>
        <button
          type="button"
          :disabled="loading"
          class="w-full rounded-xl bg-yellow-400 py-3 text-sm font-bold text-gray-900 hover:bg-yellow-300 disabled:opacity-50 transition-colors"
          @click="placeOrder"
        >
          {{ loading ? "Redirecting…" : "Pay with PayPal" }}
        </button>
      </section>
    </div>

    <!-- Order summary -->
    <aside class="rounded-2xl border bg-white p-5 h-fit">
      <h2 class="mb-4 text-base font-semibold text-gray-900">Order Summary</h2>
      <ul class="divide-y text-sm">
        <li
          v-for="line in cart.cart?.lines"
          :key="line.id"
          class="flex justify-between py-2"
        >
          <span class="text-gray-700 line-clamp-1 flex-1 mr-3"
            >{{ line.purchasable?.name }} × {{ line.quantity }}</span
          >
          <span class="font-medium text-gray-900">{{
            line.sub_total?.formatted
          }}</span>
        </li>
      </ul>
      <div
        class="mt-4 border-t pt-3 flex justify-between font-semibold text-gray-900"
      >
        <span>Total</span>
        <span>{{ cart.total }}</span>
      </div>
    </aside>
  </div>
</template>
