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
  { value: "low", label: "Low", color: "theme-badge-neutral" },
  { value: "medium", label: "Medium", color: "text-amber-600 bg-amber-50 border-amber-200" },
  { value: "high", label: "High", color: "text-orange-600 bg-orange-50 border-orange-200" },
  { value: "urgent", label: "Urgent", color: "text-rose-600 bg-rose-50 border-rose-200" },
];

const statusConfig = {
  open: { label: "Open", class: "bg-emerald-50 text-emerald-700 border-emerald-100", icon: ClockIcon },
  in_progress: { label: "In Progress", class: "bg-blue-50 text-blue-700 border-blue-100", icon: ArrowPathIcon },
  resolved: { label: "Resolved", class: "bg-brand-50 text-brand-700 border-brand-100", icon: CheckCircleIcon },
  closed: { label: "Closed", class: "theme-badge-neutral", icon: XMarkIcon },
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
        <h1 class="theme-title text-2xl font-extrabold tracking-tight sm:text-3xl">
          Help & Support
        </h1>
        <p class="theme-copy mt-1 text-sm">
          Need assistance? Submit a ticket and our team will help you shortly.
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="btn-primary inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition-all active:scale-[0.98]"
      >
        <PlusIcon class="size-4.5" />
        New Ticket
      </button>
    </div>

    <!-- Quick Help Cards -->
    <div class="mb-10 grid gap-4 sm:grid-cols-3">
      <div class="theme-card theme-card-hover rounded-2xl p-5 text-center transition">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
           <DocumentTextIcon class="size-5" />
        </div>
        <h3 class="theme-title text-sm font-bold">Knowledge Base</h3>
        <p class="theme-copy mt-1 text-xs">Browse FAQs and guides.</p>
      </div>
      <div class="theme-card theme-card-hover rounded-2xl p-5 text-center transition">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
           <ChatBubbleLeftRightIcon class="size-5" />
        </div>
        <h3 class="theme-title text-sm font-bold">Live Chat</h3>
        <p class="theme-copy mt-1 text-xs">Chat with a support agent.</p>
      </div>
      <div class="theme-card theme-card-hover rounded-2xl p-5 text-center transition">
        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
           <ExclamationCircleIcon class="size-5" />
        </div>
        <h3 class="theme-title text-sm font-bold">System Status</h3>
        <p class="theme-copy mt-1 text-xs">Check platform health.</p>
      </div>
    </div>

    <!-- Tickets List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="theme-skeleton h-24 animate-pulse rounded-2xl" />
    </div>

    <div v-else-if="error" class="rounded-2xl border border-rose-100 bg-rose-50 p-8 text-center">
       <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          <XMarkIcon class="size-6" />
       </div>
       <h3 class="mt-3 text-sm font-bold text-rose-900">Failed to load tickets</h3>
       <button @click="loadTickets" class="mt-4 text-xs font-bold text-rose-700 underline">Try again</button>
    </div>

    <div v-else-if="tickets.length === 0" class="theme-empty-state flex flex-col items-center justify-center rounded-3xl px-6 py-16 text-center">
       <div class="relative mb-6">
          <div class="absolute -inset-4 rounded-full bg-brand-50 blur-xl opacity-50"></div>
          <div class="relative flex size-20 items-center justify-center rounded-3xl bg-brand-100 text-brand-600 shadow-inner ring-1 ring-brand-200">
             <LifebuoyIcon class="size-10" />
          </div>
       </div>
       <h3 class="theme-title text-xl font-bold">How can we help?</h3>
       <p class="theme-copy mt-2 max-w-xs text-sm">You haven't submitted any support tickets yet. Click "New Ticket" to get started.</p>
    </div>

    <div v-else class="space-y-4">
       <h2 class="theme-copy text-sm font-bold uppercase tracking-widest">Your Tickets</h2>
       <div class="grid gap-3">
          <div v-for="ticket in tickets" :key="ticket.id" class="theme-card theme-card-hover group flex items-center justify-between gap-4 rounded-2xl p-4">
             <div class="flex items-center gap-4 min-w-0">
                <div class="theme-icon-muted flex size-12 shrink-0 items-center justify-center rounded-xl transition-colors group-hover:bg-brand-50 group-hover:text-brand-600">
                   <DocumentTextIcon class="size-5" />
                </div>
                <div class="min-w-0">
                   <h4 class="theme-title truncate font-bold transition-colors group-hover:text-brand-700">{{ ticket.subject }}</h4>
                   <div class="theme-copy flex items-center gap-2 text-[10px]">
                      <span>Ref: #{{ ticket.id }}</span>
                      <span v-if="ticket.sector" class="theme-badge-neutral rounded px-1.5 py-0.5 font-bold uppercase">{{ ticket.sector.replace('_', ' ') }}</span>
                      <span>•</span>
                      <span>{{ formatDate(ticket.created_at) }}</span>
                   </div>
                </div>
             </div>

             <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                  :class="statusConfig[ticket.status]?.class ?? 'theme-badge-neutral'"
                >
                   <component :is="statusConfig[ticket.status]?.icon" class="size-3" />
                   {{ statusConfig[ticket.status]?.label ?? ticket.status }}
                </span>
                <span class="inline-flex rounded-lg border px-1.5 py-0.5 text-[10px] font-bold"
                  :class="priorities.find(p => p.value === ticket.priority)?.color ?? 'theme-badge-neutral'"
                >
                  {{ priorities.find(p => p.value === ticket.priority)?.label }}
                </span>
             </div>
          </div>
       </div>
    </div>

    <!-- Create Ticket Modal -->
    <div v-if="showCreateModal" class="theme-overlay fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="theme-modal w-full max-w-lg rounded-3xl p-8 shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="mb-6 flex items-center justify-between">
          <h2 class="theme-title text-2xl font-black tracking-tight">Open Support Ticket</h2>
          <button @click="showCreateModal = false" class="theme-copy rounded-xl p-2 transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text)]">
            <XMarkIcon class="size-6" />
          </button>
        </div>

        <form @submit.prevent="submitTicket" class="space-y-5">
           <div>
              <label class="theme-copy mb-1.5 ml-1 block text-[10px] font-bold uppercase tracking-widest">Subject</label>
              <input v-model="form.subject" required class="theme-input w-full rounded-2xl px-4 py-3 text-sm transition-all shadow-inner" placeholder="E.g. Cannot complete payment" />
           </div>

           <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="theme-copy mb-1.5 ml-1 block text-[10px] font-bold uppercase tracking-widest">Sector</label>
                <select v-model="form.sector" class="theme-input w-full rounded-2xl px-4 py-3 text-sm transition-all shadow-inner">
                   <option v-for="sec in sectors" :key="sec.value" :value="sec.value">{{ sec.label }}</option>
                </select>
              </div>
              <div>
                <label class="theme-copy mb-1.5 ml-1 block text-[10px] font-bold uppercase tracking-widest">Category</label>
                <select v-model="form.category" class="theme-input w-full rounded-2xl px-4 py-3 text-sm transition-all shadow-inner">
                   <option v-for="cat in filteredCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
              </div>
           </div>

           <div>
              <label class="theme-copy mb-1.5 ml-1 block text-[10px] font-bold uppercase tracking-widest">Priority</label>
              <select v-model="form.priority" class="theme-input w-full rounded-2xl px-4 py-3 text-sm transition-all shadow-inner">
                 <option v-for="prio in priorities" :key="prio.value" :value="prio.value">{{ prio.label }}</option>
              </select>
           </div>

           <div>
              <label class="theme-copy mb-1.5 ml-1 block text-[10px] font-bold uppercase tracking-widest">Message</label>
              <textarea v-model="form.message" required rows="4" class="theme-input w-full rounded-2xl px-4 py-3 text-sm transition-all shadow-inner" placeholder="Please provide detailed information..."></textarea>
           </div>

           <div class="flex items-center gap-3 pt-4">
              <button
                type="submit"
                :disabled="submitting"
                class="btn-primary flex-1 rounded-2xl py-4 text-base font-black transition-all active:scale-95 disabled:opacity-50"
              >
                {{ submitting ? 'Submitting...' : 'Submit Support Ticket' }}
              </button>
              <button
                type="button"
                @click="showCreateModal = false"
                class="btn-secondary rounded-2xl px-6 py-4 text-sm font-bold"
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
