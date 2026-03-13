<script setup>
import { ref, computed, onMounted, watch } from "vue";
import {
  LifebuoyIcon,
  PlusIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
  ArrowPathIcon,
  XMarkIcon
} from "@heroicons/vue/24/outline";
import { supportApi } from "@/api/support";

const tickets = ref([]);
const loading = ref(true);
const error = ref(false);
const showCreateModal = ref(false);
const submitting = ref(false);

const form = ref({
  subject: "",
  category: "general",
  sector: "",
  priority: "medium",
  message: ""
});

const sectors = [
  { value: "", label: "General / Global" },
  { value: "ecommerce", label: "E-Commerce" },
  { value: "real_estate", label: "Real Estate" },
  { value: "paupahan", label: "Rentals / Paupahan" },
  { value: "lipat_bahay", label: "Moving / Lipat Bahay" },
];

const categories = {
  universal: [
    { value: "general", label: "General Inquiry" },
    { value: "account_issue", label: "Account Issue" },
    { value: "payment_issue", label: "Payment Issue" },
  ],
  ecommerce: [
    { value: "order_issue", label: "Order Issue" },
    { value: "product_issue", label: "Product Issue" },
    { value: "delivery_issue", label: "Delivery Issue" },
    { value: "refund_request", label: "Refund Request" },
  ],
  real_estate: [
    { value: "property_inquiry", label: "Property Inquiry" },
    { value: "viewing_issue", label: "Viewing Issue" },
    { value: "agent_dispute", label: "Agent Dispute" },
    { value: "documentation", label: "Documentation" },
  ],
  paupahan: [
    { value: "rental_agreement", label: "Rental Agreement" },
    { value: "maintenance_request", label: "Maintenance Request" },
    { value: "landlord_issue", label: "Landlord Issue" },
    { value: "deposit_issue", label: "Deposit Issue" },
  ],
  lipat_bahay: [
    { value: "booking_schedule", label: "Booking Schedule" },
    { value: "damage_report", label: "Damage Report" },
    { value: "mover_behavior", label: "Mover Behavior" },
    { value: "pricing_dispute", label: "Pricing Dispute" },
  ],
};

const filteredCategories = computed(() => {
  const base = [...categories.universal];
  if (form.value.sector && categories[form.value.sector]) {
    base.push(...categories[form.value.sector]);
  }
  return base;
});

// Reset category when sector changes
watch(() => form.value.sector, () => {
  form.value.category = "general";
});

const priorities = [
  { value: "low", label: "Low", color: "text-slate-500 bg-slate-50 border-slate-200" },
  { value: "medium", label: "Medium", color: "text-amber-600 bg-amber-50 border-amber-200" },
  { value: "high", label: "High", color: "text-orange-600 bg-orange-50 border-orange-200" },
  { value: "urgent", label: "Urgent", color: "text-rose-600 bg-rose-50 border-rose-200" },
];

const statusConfig = {
  open: { label: "Open", class: "bg-emerald-50 text-emerald-700 border-emerald-100", icon: ClockIcon },
  in_progress: { label: "In Progress", class: "bg-blue-50 text-blue-700 border-blue-100", icon: ArrowPathIcon },
  resolved: { label: "Resolved", class: "bg-brand-50 text-brand-700 border-brand-100", icon: CheckCircleIcon },
  closed: { label: "Closed", class: "bg-slate-100 text-slate-600 border-slate-200", icon: XMarkIcon },
};

async function loadTickets() {
  loading.value = true;
  error.value = false;
  try {
    const res = await supportApi.list();
    tickets.value = res.data.data ?? res.data;
  } catch (err) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

async function submitTicket() {
  submitting.value = true;
  try {
    await supportApi.create(form.value);
    showCreateModal.value = false;
    form.value = { subject: "", category: "general", priority: "medium", message: "" };
    await loadTickets();
  } catch (err) {
    alert("Failed to submit ticket. Please try again.");
  } finally {
    submitting.value = false;
  }
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric"
  });
}

onMounted(() => {
  loadTickets();
});
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
          Help & Support
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Need assistance? Submit a ticket and our team will help you shortly.
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm ring-1 ring-brand-700 transition-all hover:bg-brand-700 active:scale-[0.98]"
      >
        <PlusIcon class="size-4.5" />
        New Ticket
      </button>
    </div>

    <!-- Quick Help Cards -->
    <div class="mb-10 grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center transition hover:border-brand-200 hover:shadow-sm">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
           <DocumentTextIcon class="size-5" />
        </div>
        <h3 class="text-sm font-bold text-slate-900">Knowledge Base</h3>
        <p class="mt-1 text-xs text-slate-500">Browse FAQs and guides.</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center transition hover:border-brand-200 hover:shadow-sm">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
           <ChatBubbleLeftRightIcon class="size-5" />
        </div>
        <h3 class="text-sm font-bold text-slate-900">Live Chat</h3>
        <p class="mt-1 text-xs text-slate-500">Chat with a support agent.</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center transition hover:border-brand-200 hover:shadow-sm">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
           <ExclamationCircleIcon class="size-5" />
        </div>
        <h3 class="text-sm font-bold text-slate-900">System Status</h3>
        <p class="mt-1 text-xs text-slate-500">Check platform health.</p>
      </div>
    </div>

    <!-- Tickets List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-24 animate-pulse rounded-2xl bg-white ring-1 ring-slate-200" />
    </div>

    <div v-else-if="error" class="rounded-2xl border border-rose-100 bg-rose-50 p-8 text-center">
       <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          <XMarkIcon class="size-6" />
       </div>
       <h3 class="mt-3 text-sm font-bold text-rose-900">Failed to load tickets</h3>
       <button @click="loadTickets" class="mt-4 text-xs font-bold text-rose-700 underline">Try again</button>
    </div>

    <div v-else-if="tickets.length === 0" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white py-16 px-6 text-center shadow-sm">
       <div class="relative mb-6">
          <div class="absolute -inset-4 rounded-full bg-brand-50 blur-xl opacity-50"></div>
          <div class="relative flex size-20 items-center justify-center rounded-3xl bg-brand-100 text-brand-600 shadow-inner ring-1 ring-brand-200">
             <LifebuoyIcon class="size-10" />
          </div>
       </div>
       <h3 class="text-xl font-bold text-slate-900">How can we help?</h3>
       <p class="mt-2 max-w-xs text-sm text-slate-500">You haven't submitted any support tickets yet. Click "New Ticket" to get started.</p>
    </div>

    <div v-else class="space-y-4">
       <h2 class="text-sm font-bold uppercase tracking-widest text-slate-400">Your Tickets</h2>
       <div class="grid gap-3">
          <div v-for="ticket in tickets" :key="ticket.id" class="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-brand-200 hover:shadow-md">
             <div class="flex items-center gap-4 min-w-0">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-400 ring-1 ring-slate-100 group-hover:bg-brand-50 group-hover:text-brand-600 transition-colors">
                   <DocumentTextIcon class="size-5" />
                </div>
                <div class="min-w-0">
                   <h4 class="font-bold text-slate-900 truncate group-hover:text-brand-700 transition-colors">{{ ticket.subject }}</h4>
                   <div class="flex items-center gap-2 text-[10px] text-slate-400">
                      <span>Ref: #{{ ticket.id }}</span>
                      <span v-if="ticket.sector" class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold uppercase">{{ ticket.sector.replace('_', ' ') }}</span>
                      <span>•</span>
                      <span>{{ formatDate(ticket.created_at) }}</span>
                   </div>
                </div>
             </div>

             <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                  :class="statusConfig[ticket.status]?.class ?? 'bg-slate-50 text-slate-500'"
                >
                   <component :is="statusConfig[ticket.status]?.icon" class="size-3" />
                   {{ statusConfig[ticket.status]?.label ?? ticket.status }}
                </span>
                <span class="inline-flex rounded-lg border px-1.5 py-0.5 text-[10px] font-bold"
                  :class="priorities.find(p => p.value === ticket.priority)?.color ?? 'bg-slate-50'"
                >
                  {{ priorities.find(p => p.value === ticket.priority)?.label }}
                </span>
             </div>
          </div>
       </div>
    </div>

    <!-- Create Ticket Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl bg-white p-8 shadow-2xl ring-1 ring-slate-200 animate-in fade-in zoom-in duration-200">
        <div class="mb-6 flex items-center justify-between">
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Open Support Ticket</h2>
          <button @click="showCreateModal = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            <XMarkIcon class="size-6" />
          </button>
        </div>

        <form @submit.prevent="submitTicket" class="space-y-5">
           <div>
              <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Subject</label>
              <input v-model="form.subject" required class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="E.g. Cannot complete payment" />
           </div>

           <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Sector</label>
                <select v-model="form.sector" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner">
                   <option v-for="sec in sectors" :key="sec.value" :value="sec.value">{{ sec.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Category</label>
                <select v-model="form.category" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner">
                   <option v-for="cat in filteredCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
              </div>
           </div>

           <div>
              <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Priority</label>
              <select v-model="form.priority" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner">
                 <option v-for="prio in priorities" :key="prio.value" :value="prio.value">{{ prio.label }}</option>
              </select>
           </div>

           <div>
              <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 ml-1">Message</label>
              <textarea v-model="form.message" required rows="4" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-500 focus:bg-white focus:ring-brand-500 transition-all shadow-inner" placeholder="Please provide detailed information..."></textarea>
           </div>

           <div class="flex items-center gap-3 pt-4">
              <button
                type="submit"
                :disabled="submitting"
                class="flex-1 rounded-2xl bg-brand-600 py-4 text-base font-black text-white shadow-xl shadow-brand-500/20 transition-all hover:bg-brand-700 active:scale-95 disabled:opacity-50"
              >
                {{ submitting ? 'Submitting...' : 'Submit Support Ticket' }}
              </button>
              <button
                type="button"
                @click="showCreateModal = false"
                class="rounded-2xl border border-slate-200 bg-white px-6 py-4 text-sm font-bold text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
           </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-mono {
  font-family: 'Space Mono', 'JetBrains Mono', monospace;
}
</style>
