<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  HomeModernIcon,
  ChevronRightIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  ChatBubbleLeftRightIcon,
} from "@heroicons/vue/24/outline";
import { agreementsApi } from "@/api/agreements";

const agreements = ref([]);
const loading = ref(true);
const error = ref(false);

const actingOn = ref(null);
const questionText = ref("");
const actionLoading = ref(false);

async function loadAgreements() {
  loading.value = true;
  try {
    const { data } = await agreementsApi.list();
    agreements.value = data.data ?? data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadAgreements();
});

async function acceptAgreement(id) {
  if (!confirm("Are you sure you want to sign and accept this agreement?")) return;
  actionLoading.value = true;
  try {
    await agreementsApi.update(id, { status: "signed" });
    await loadAgreements();
  } catch (err) {
    alert("Failed to sign agreement");
  } finally {
    actionLoading.value = false;
  }
}

async function submitQuestion(id) {
  if (!questionText.value.trim()) return;
  actionLoading.value = true;
  try {
    await agreementsApi.update(id, { 
      status: "negotiating",
      tenant_questions: questionText.value 
    });
    actingOn.value = null;
    questionText.value = "";
    await loadAgreements();
  } catch (err) {
    alert("Failed to submit question");
  } finally {
    actionLoading.value = false;
  }
}

function formatMoney(amount) {
  if (amount == null) return "—";
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount / 100);
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="mb-6">
      <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
        Rental Agreements
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Your finalized and signed property rental contracts.
      </p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 4"
        :key="i"
        class="h-20 animate-pulse rounded-2xl bg-slate-100"
      />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="rounded-2xl border border-red-100 bg-red-50 py-8 text-center text-sm text-red-600"
    >
      Failed to load rental agreements. Please refresh the page.
    </div>

    <!-- Empty -->
    <div
      v-else-if="agreements.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white py-12 text-center"
    >
      <DocumentTextIcon class="mx-auto mb-3 size-10 text-emerald-200" />
      <p class="font-medium text-slate-500">No rental agreements yet</p>
      <p class="mt-1 text-sm text-slate-400">
        When a landlord confirms your rental application, it will appear here.
      </p>
    </div>

    <!-- Agreements list -->
    <ul v-else class="space-y-4">
      <li v-for="agreement in agreements" :key="agreement.id" class="rounded-2xl border bg-white shadow-sm transition-all"
        :class="agreement.status === 'pending' || agreement.status === 'negotiating' ? 'border-brand-200' : 'border-emerald-100'">
        <div class="p-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-4 sm:items-center">
            <img
              v-if="agreement.property?.featured_image"
              :src="agreement.property.featured_image"
              :alt="agreement.property.title"
              class="size-16 shrink-0 rounded-xl object-cover ring-1 ring-slate-100"
            />
            <div
              v-else
              class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200"
            >
              <HomeModernIcon class="size-6" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span
                  v-if="agreement.status === 'pending' || agreement.status === 'negotiating'"
                  class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-brand-700 uppercase"
                >
                  Review Required
                </span>
                <span
                  v-else
                  class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-emerald-700 uppercase"
                >
                  Active Contract
                </span>
              </div>
              <RouterLink :to="`/properties/${agreement.property?.slug}`" class="mt-1 block truncate font-bold text-slate-900 transition hover:text-brand-600">
                {{ agreement.property?.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">
                {{ agreement.store?.name }}
                <span v-if="agreement.property?.city">
                  · {{ agreement.property.city }}
                </span>
              </p>
            </div>
          </div>

          <div
            class="flex flex-col gap-2 rounded-xl bg-slate-50 p-3 sm:items-end sm:bg-transparent sm:p-0 text-left sm:text-right"
          >
            <p class="text-sm font-semibold text-slate-800">
              {{ formatMoney(agreement.monthly_rent) }}
              <span class="text-xs font-normal text-slate-500">/ mo</span>
            </p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 sm:justify-end">
              <p>Move-In: <span class="font-medium text-slate-700">{{ agreement.move_in_date || 'N/A' }}</span></p>
              <p v-if="agreement.lease_term_months">Term: <span class="font-medium text-slate-700">{{ agreement.lease_term_months }} months</span></p>
            </div>
          </div>
        </div>

        <!-- Pending Action Area -->
        <div v-if="agreement.status === 'pending' || agreement.status === 'negotiating'" class="border-t border-slate-100 bg-slate-50 p-5 rounded-b-2xl">
          <div v-if="actingOn !== agreement.id" class="flex flex-wrap items-center gap-3">
             <button
               @click="acceptAgreement(agreement.id)"
               :disabled="actionLoading"
               class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
             >
               <CheckCircleIcon class="size-4" />
               Accept & Sign
             </button>
             <button
               @click="actingOn = agreement.id; questionText = ''"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
             >
               <ChatBubbleLeftRightIcon class="size-4 text-slate-400" />
               I have a question
             </button>
          </div>
          <div v-else class="space-y-3">
             <label class="block text-sm font-semibold text-slate-900">What questions do you have for the landlord?</label>
             <textarea
               v-model="questionText"
               rows="3"
               class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
               placeholder="Example: Can we move the move-in date by 2 days?"
             ></textarea>
             <div class="flex items-center gap-2">
                <button
                 @click="submitQuestion(agreement.id)"
                 :disabled="actionLoading || !questionText.trim()"
                 class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
               >
                 Submit Question
               </button>
               <button
                 @click="actingOn = null"
                 class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition"
               >
                 Cancel
               </button>
             </div>
          </div>
          
          <!-- Show previous question if negotiating -->
           <div v-if="agreement.tenant_questions" class="mt-4 rounded-xl bg-yellow-50 p-3 text-sm text-yellow-800 border border-yellow-100">
             <strong>Your question to the landlord:</strong>
             <p class="mt-1 italic">"{{ agreement.tenant_questions }}"</p>
           </div>
           
           <!-- Landlord Response Block -->
           <div v-if="agreement.landlord_response" class="mt-3 rounded-xl bg-brand-50 p-3 text-sm text-brand-800 border border-brand-100">
             <strong>Response from Landlord:</strong>
             <p class="mt-1">"{{ agreement.landlord_response }}"</p>
           </div>
        </div>
      </li>
    </ul>
  </div>
</template>
