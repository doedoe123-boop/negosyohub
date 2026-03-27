<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  HomeModernIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  ChatBubbleLeftRightIcon,
  TruckIcon,
  ShieldCheckIcon,
  ClipboardDocumentCheckIcon,
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

function agreementJourney(agreement) {
  const status = agreement.status;

  return [
    {
      key: "inquiry",
      label: "Inquiry",
      done: true,
      description: "You expressed interest in this rental.",
    },
    {
      key: "agreement",
      label: "Agreement",
      done: ["signed", "active"].includes(status),
      active: ["pending", "negotiating"].includes(status),
      description:
        status === "negotiating"
          ? "The landlord is reviewing your questions and can update the agreement terms."
          : status === "pending"
            ? "Review the rental agreement details and sign when you're ready."
            : "Rental agreement confirmed.",
    },
    {
      key: "move_in",
      label: "Move-In Prep",
      done: false,
      active: ["signed", "active"].includes(status),
      description:
        ["signed", "active"].includes(status)
          ? "Prepare your transfer, utilities, and moving schedule."
          : "Available after agreement confirmation.",
    },
  ];
}

function moversLink(agreement) {
  return {
    path: "/movers",
    query: {
      rental_id: String(agreement.id),
      city: agreement.property?.city ?? "",
      delivery_city: agreement.property?.city ?? "",
      delivery_address: agreement.property?.full_address ?? agreement.property?.address_line ?? "",
      scheduled_at: agreement.move_in_date
        ? `${agreement.move_in_date}T09:00`
        : "",
    },
  };
}

function reportLink(agreement) {
  return {
    path: "/account/help",
    query: {
      open: "1",
      sector: "paupahan",
      category: "landlord_issue",
      priority: "high",
      store_id: agreement.store?.id ? String(agreement.store.id) : "",
      subject: `Report rental issue: ${agreement.property?.title ?? "Property listing"}`,
      message:
        "I want to report a safety concern or suspicious request related to this rental listing/agreement. Please review this case before any payment is made.",
    },
  };
}

function statusBadge(agreement) {
  if (agreement.status === "pending") {
    return {
      label: agreement.status_label ?? "Pending Review",
      classes: "bg-brand-100 text-brand-700",
    };
  }

  if (agreement.status === "negotiating") {
    return {
      label: agreement.status_label ?? "Negotiating",
      classes: "bg-amber-100 text-amber-700",
    };
  }

  return {
    label: agreement.status_label ?? "Signed",
    classes: "bg-emerald-100 text-emerald-700",
  };
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="mb-6">
      <h1 class="theme-title text-2xl font-extrabold tracking-tight">
        Rental Agreements
      </h1>
      <p class="theme-copy mt-1 text-sm">
        Review, question, and sign your rental agreements without leaving the platform.
      </p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="theme-skeleton h-20 animate-pulse rounded-2xl" />
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
      class="theme-empty-state rounded-2xl py-12 text-center"
    >
      <DocumentTextIcon class="mx-auto mb-3 size-10 text-emerald-200" />
      <p class="theme-copy font-medium">No rental agreements yet</p>
      <p class="theme-copy mt-1 text-sm">
        When a landlord converts your inquiry into a rental agreement, it will appear here for review.
      </p>
    </div>

    <!-- Agreements list -->
    <ul v-else class="space-y-4">
      <li v-for="agreement in agreements" :key="agreement.id" class="theme-card rounded-2xl shadow-sm transition-all"
        :class="agreement.status === 'pending' || agreement.status === 'negotiating' ? 'border-brand-200' : 'border-emerald-100'">
        <div class="p-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-4 sm:items-center">
            <img
              v-if="agreement.property?.featured_image"
              :src="agreement.property.featured_image"
              :alt="agreement.property.title"
              class="size-16 shrink-0 rounded-xl object-cover"
              style="box-shadow: inset 0 0 0 1px var(--color-border)"
            />
            <div
              v-else
              class="theme-icon-muted flex size-16 shrink-0 items-center justify-center rounded-xl"
            >
              <HomeModernIcon class="size-6" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                  :class="statusBadge(agreement).classes"
                >
                  {{ statusBadge(agreement).label }}
                </span>
              </div>
              <RouterLink :to="`/properties/${agreement.property?.slug}`" class="theme-title mt-1 block truncate font-bold transition hover:text-brand-600">
                {{ agreement.property?.title }}
              </RouterLink>
              <p class="theme-copy text-xs">
                {{ agreement.store?.name }}
                <span v-if="agreement.property?.city">
                  · {{ agreement.property.city }}
                </span>
              </p>
              <p v-if="agreement.property?.full_address" class="theme-copy mt-1 text-xs">
                {{ agreement.property.full_address }}
              </p>
            </div>
          </div>

          <div
            class="flex flex-col gap-2 rounded-xl p-3 text-left sm:items-end sm:bg-transparent sm:p-0 sm:text-right"
          >
            <p class="theme-title text-sm font-semibold">
              {{ formatMoney(agreement.monthly_rent) }}
              <span class="theme-copy text-xs font-normal">/ mo</span>
            </p>
            <div class="theme-copy flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:justify-end">
              <p>Move-In: <span class="theme-title font-medium">{{ agreement.move_in_date || 'N/A' }}</span></p>
              <p v-if="agreement.lease_term_months">Term: <span class="theme-title font-medium">{{ agreement.lease_term_months }} months</span></p>
            </div>
          </div>
        </div>

        <!-- Pending Action Area -->
        <div v-if="agreement.status === 'pending' || agreement.status === 'negotiating'" class="theme-card-muted theme-divider-soft rounded-b-2xl border-t p-5">
          <div class="mb-4 rounded-2xl border border-brand-200/60 bg-brand-50/70 px-4 py-3 dark:bg-brand-900/20 dark:border-brand-800/50">
            <p class="theme-title text-sm font-bold">
              {{ agreement.tenant_primary_action ?? "Review and Sign" }}
            </p>
            <p class="theme-copy mt-1 text-xs leading-relaxed">
              {{
                agreement.status === "pending"
                  ? "Your landlord prepared this agreement from your rental inquiry. Review the terms, ask questions if needed, or sign to confirm."
                  : "Your agreement is back in review. Read the latest response below, then sign when the terms look right."
              }}
            </p>
          </div>
          <div v-if="actingOn !== agreement.id" class="flex flex-wrap items-center gap-3">
             <button
               @click="acceptAgreement(agreement.id)"
               :disabled="actionLoading || !agreement.can_sign"
               class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
             >
               <CheckCircleIcon class="size-4" />
               {{ agreement.tenant_primary_action ?? "Accept & Sign" }}
             </button>
             <button
               @click="actingOn = agreement.id; questionText = ''"
               class="btn-secondary inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold transition"
             >
               <ChatBubbleLeftRightIcon class="theme-copy size-4" />
               I have a question
             </button>
          </div>
          <div v-else class="space-y-3">
             <label class="theme-title block text-sm font-semibold">What questions do you have for the landlord?</label>
             <textarea
               v-model="questionText"
               rows="3"
               class="theme-input w-full rounded-xl px-3 py-2 shadow-sm sm:text-sm"
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
                 class="theme-copy px-3 py-2 text-sm font-medium transition hover:text-[var(--color-text)]"
               >
                 Cancel
               </button>
             </div>
          </div>
          
          <!-- Show previous question if negotiating -->
           <div v-if="agreement.tenant_questions" class="mt-4 rounded-xl bg-yellow-50 p-3 text-sm text-yellow-800 border border-yellow-100 dark:bg-yellow-900/20 dark:border-yellow-800/50 dark:text-yellow-200">
             <strong class="dark:text-yellow-100">Your question to the landlord:</strong>
             <p class="mt-1 italic">"{{ agreement.tenant_questions }}"</p>
           </div>
           
           <!-- Landlord Response Block -->
           <div v-if="agreement.landlord_response" class="mt-3 rounded-xl bg-brand-50 p-3 text-sm text-brand-800 border border-brand-100 dark:bg-brand-900/20 dark:border-brand-800/50 dark:text-brand-200">
             <strong class="dark:text-brand-100">Response from Landlord:</strong>
             <p class="mt-1">"{{ agreement.landlord_response }}"</p>
           </div>
        </div>

        <div class="theme-card-muted theme-divider-soft rounded-b-2xl border-t p-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="theme-title text-sm font-bold">Safe Move-In Journey</p>
              <p class="theme-copy mt-1 text-xs">
                Stay inside the platform from agreement review to moving day.
              </p>
            </div>
            <ShieldCheckIcon class="size-5 text-emerald-500" />
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <div
              v-for="step in agreementJourney(agreement)"
              :key="step.key"
              class="rounded-2xl border p-3"
              :class="step.done
                ? 'border-emerald-500/30 bg-emerald-500/10 dark:border-emerald-500/20 dark:bg-emerald-500/5'
                : step.active
                  ? 'border-brand-500/30 bg-brand-500/10 dark:border-brand-500/20 dark:bg-brand-500/5'
                  : 'theme-divider-soft theme-card'"
            >
              <p class="theme-title text-sm font-semibold inline-flex items-center gap-1.5">
                {{ step.label }}
              </p>
              <p class="theme-copy mt-1 text-xs leading-relaxed">
                {{ step.description }}
              </p>
            </div>
          </div>

          <div
            v-if="agreement.status === 'signed' || agreement.status === 'active'"
            class="mt-4 grid gap-4 lg:grid-cols-[1.4fr_1fr]"
          >
            <div class="rounded-2xl border border-brand-500/20 bg-brand-500/10 dark:border-brand-500/20 dark:bg-brand-900/10 p-4">
              <div class="flex items-start gap-3">
                <TruckIcon class="mt-0.5 size-5 text-brand-500" />
                <div>
                  <p class="theme-title text-sm font-bold">Ready to move in?</p>
                  <p class="theme-copy mt-1 text-sm leading-relaxed">
                    We can prefill your destination from this rental agreement so
                    you can book a Lipat Bahay move faster and stay on-platform.
                  </p>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap gap-3">
                <RouterLink
                  :to="moversLink(agreement)"
                  class="btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold"
                >
                  <TruckIcon class="size-4" />
                  Book a Moving Service
                </RouterLink>
                <RouterLink
                  to="/deals"
                  class="btn-secondary inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold"
                >
                  <ClipboardDocumentCheckIcon class="size-4" />
                  Browse Move-In Essentials
                </RouterLink>
              </div>
            </div>

            <div class="theme-card rounded-2xl border-dashed p-4">
              <p class="theme-title text-sm font-bold">Move-In Checklist</p>
              <ul class="theme-copy mt-3 space-y-2 text-xs leading-relaxed">
                <li>Activate utilities and internet before move-in day.</li>
                <li>Confirm building access, parking, and elevator schedule.</li>
                <li>Keep all payments and proof of agreement inside the platform.</li>
              </ul>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-3">
            <RouterLink
              :to="reportLink(agreement)"
              class="theme-copy inline-flex items-center gap-2 text-xs font-semibold underline underline-offset-2 hover:text-[var(--color-text)]"
            >
              Report a suspicious landlord request
            </RouterLink>
            <span class="theme-copy text-xs">
              Never send deposits or reservation fees outside NegosyoHub.
            </span>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
