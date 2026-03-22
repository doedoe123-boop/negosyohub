<script setup>
import { ref, onMounted } from "vue";
import {
  MapPinIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";
import { addressesApi } from "@/api/addresses";

const addresses = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const errors = ref({});

const emptyForm = () => ({
  label: "",
  line1: "",
  line2: "",
  barangay: "",
  city: "",
  province: "",
  postal_code: "",
  is_default: false,
});

const form = ref(emptyForm());
const editingId = ref(null);

onMounted(fetchAddresses);

async function fetchAddresses() {
  loading.value = true;

  try {
    const { data } = await addressesApi.list();
    addresses.value = data.data ?? data;
  } finally {
    loading.value = false;
  }
}

function openAdd() {
  editingId.value = null;
  form.value = emptyForm();
  errors.value = {};
  showModal.value = true;
}

function openEdit(address) {
  editingId.value = address.id;
  form.value = {
    label: address.label ?? "",
    line1: address.line1 ?? "",
    line2: address.line2 ?? "",
    barangay: address.barangay ?? "",
    city: address.city ?? "",
    province: address.province ?? "",
    postal_code: address.postal_code ?? "",
    is_default: address.is_default ?? false,
  };
  errors.value = {};
  showModal.value = true;
}

async function save() {
  errors.value = {};

  const required = ["line1", "city", "province", "postal_code"];
  const clientErrors = {};
  required.forEach((field) => {
    if (!form.value[field]?.trim()) {
      clientErrors[field] = ["This field is required."];
    }
  });
  if (Object.keys(clientErrors).length) {
    errors.value = clientErrors;
    return;
  }

  saving.value = true;

  try {
    if (editingId.value) {
      await addressesApi.update(editingId.value, form.value);
    } else {
      await addressesApi.store(form.value);
    }
    showModal.value = false;
    await fetchAddresses();
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {};
    }
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm("Remove this address?")) {
    return;
  }

  deletingId.value = id;

  try {
    await addressesApi.destroy(id);
    addresses.value = addresses.value.filter((a) => a.id !== id);
  } finally {
    deletingId.value = null;
  }
}

async function setDefault(id) {
  await addressesApi.setDefault(id);
  await fetchAddresses();
}
</script>

<template>
  <div class="mx-auto max-w-2xl px-4 py-8 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="theme-title text-2xl font-extrabold tracking-tight">
        Addresses
      </h1>
      <button
        class="btn-primary flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition-all"
        @click="openAdd"
      >
        <PlusIcon class="size-4" />
        Add New
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="theme-skeleton h-24 animate-pulse rounded-2xl" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="addresses.length === 0"
      class="theme-empty-state rounded-2xl py-14 text-center"
    >
      <MapPinIcon class="theme-copy mx-auto mb-3 size-10" />
      <p class="theme-copy font-medium">No saved addresses</p>
      <p class="theme-copy mt-1 text-sm">
        Add an address to speed up checkout.
      </p>
    </div>

    <!-- List -->
    <ul v-else class="space-y-3">
      <li
        v-for="addr in addresses"
        :key="addr.id"
        class="theme-card rounded-2xl p-5 transition-colors"
        :class="addr.is_default ? 'border-brand-300' : ''"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
              <span class="theme-title text-sm font-semibold">{{
                addr.label || "Address"
              }}</span>
              <span
                v-if="addr.is_default"
                class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700"
                >Default</span
              >
            </div>
            <p class="theme-copy text-sm leading-relaxed">
              {{ addr.line1
              }}<template v-if="addr.line2">, {{ addr.line2 }}</template>
              <br />
              <template v-if="addr.barangay">{{ addr.barangay }}, </template>
              {{ addr.city }}, {{ addr.province }} {{ addr.postal_code }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex shrink-0 items-center gap-1">
            <button
              v-if="!addr.is_default"
              class="theme-copy rounded-lg px-3 py-1.5 text-xs font-medium transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text)]"
              @click="setDefault(addr.id)"
            >
              Set default
            </button>
            <button
              class="theme-copy rounded-lg p-1.5 transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text)]"
              @click="openEdit(addr)"
            >
              <PencilIcon class="size-4" />
            </button>
            <button
              class="theme-copy rounded-lg p-1.5 transition-colors hover:bg-red-50 hover:text-red-500"
              :disabled="deletingId === addr.id"
              @click="remove(addr.id)"
            >
              <TrashIcon class="size-4" />
            </button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="theme-overlay fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
        @click.self="showModal = false"
      >
        <div class="theme-modal w-full max-w-lg rounded-2xl p-6 shadow-xl" @click.stop>
          <h2 class="theme-title mb-5 text-lg font-bold">
            {{ editingId ? "Edit Address" : "New Address" }}
          </h2>

          <form class="space-y-4" @submit.prevent="save">
            <!-- Label -->
            <div>
              <label
                for="addr-label"
                class="theme-copy mb-1 block text-xs font-medium"
                >Label (e.g. Home, Office)</label
              >
              <input
                id="addr-label"
                v-model="form.label"
                type="text"
                placeholder="Home"
                class="theme-input w-full rounded-xl px-3 py-2 text-sm"
              />
            </div>

            <!-- Line 1 -->
            <div>
              <label
                for="addr-line1"
                class="theme-copy mb-1 block text-xs font-medium"
                >Address Line 1 <span class="text-red-500">*</span></label
              >
              <input
                id="addr-line1"
                v-model="form.line1"
                type="text"
                required
                placeholder="House no., Street"
                class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                :class="errors.line1 ? 'border-red-300' : ''"
              />
              <p v-if="errors.line1" class="mt-0.5 text-xs text-red-600">
                {{ errors.line1[0] }}
              </p>
            </div>

            <!-- Line 2 -->
            <div>
              <label
                for="addr-line2"
                class="theme-copy mb-1 block text-xs font-medium"
                >Address Line 2 (optional)</label
              >
              <input
                id="addr-line2"
                v-model="form.line2"
                type="text"
                placeholder="Building, Floor, Unit"
                class="theme-input w-full rounded-xl px-3 py-2 text-sm"
              />
            </div>

            <!-- Barangay / City / Province / Postal -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="theme-copy mb-1 block text-xs font-medium"
                  >Barangay</label
                >
                <input
                  v-model="form.barangay"
                  type="text"
                  class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label
                  for="addr-city"
                  class="theme-copy mb-1 block text-xs font-medium"
                  >City / Municipality
                  <span class="text-red-500">*</span></label
                >
                <input
                  id="addr-city"
                  v-model="form.city"
                  type="text"
                  required
                  class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                  :class="errors.city ? 'border-red-300' : ''"
                />
              </div>
              <div>
                <label
                  for="addr-province"
                  class="theme-copy mb-1 block text-xs font-medium"
                  >Province <span class="text-red-500">*</span></label
                >
                <input
                  id="addr-province"
                  v-model="form.province"
                  type="text"
                  required
                  class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                  :class="
                    errors.province ? 'border-red-300' : ''
                  "
                />
              </div>
              <div>
                <label
                  for="addr-postal"
                  class="theme-copy mb-1 block text-xs font-medium"
                  >Postal Code <span class="text-red-500">*</span></label
                >
                <input
                  id="addr-postal"
                  v-model="form.postal_code"
                  type="text"
                  required
                  class="theme-input w-full rounded-xl px-3 py-2 text-sm"
                  :class="
                    errors.postal_code ? 'border-red-300' : ''
                  "
                />
              </div>
            </div>

            <!-- Default toggle -->
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model="form.is_default"
                type="checkbox"
                class="size-4 rounded accent-brand-600"
                style="border-color: var(--color-border)"
              />
              <span class="theme-title text-sm"
                >Set as default delivery address</span
              >
            </label>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-1">
              <button
                type="button"
                class="btn-secondary rounded-xl px-4 py-2 text-sm font-medium transition-colors"
                @click="showModal = false"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="btn-primary rounded-xl px-5 py-2 text-sm font-bold transition-all disabled:opacity-60"
              >
                {{ saving ? "Saving…" : "Save Address" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
