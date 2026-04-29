<script setup lang="ts">
import { onMounted } from 'vue';
import MerchantPromoPopup from '@/components/MerchantPromoPopup.vue';

withDefaults(
    defineProps<{
        canRegister?: boolean;
        isAuthenticated?: boolean;
    }>(),
    {
        canRegister: true,
        isAuthenticated: false,
    },
);

const dashboardUrl = '/dashboard';
const loginUrl = '/login';
const registerUrl = '/register';

onMounted(() => {
    document.title = 'Grow your business';
});
</script>

<template>
    <div class="min-h-screen bg-[#f7f3ea] text-[#163121]">
        <header
            class="relative overflow-hidden border-b border-[#1c4f38]/10 bg-[#fbf7ef]"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,214,107,0.18),_transparent_35%),radial-gradient(circle_at_right,_rgba(24,99,67,0.12),_transparent_45%)]"
            />
            <div class="relative mx-auto max-w-6xl px-6 py-6 lg:px-8">
                <nav class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-semibold tracking-[0.28em] text-[#b36d27] uppercase"
                        >
                            Merchant Marketplace
                        </p>
                        <h1
                            class="mt-2 font-serif text-2xl font-semibold text-[#113826]"
                        >
                            Launch your store with a stronger first impression.
                        </h1>
                    </div>

                    <div class="flex items-center gap-3 text-sm">
                        <a
                            v-if="isAuthenticated"
                            :href="dashboardUrl"
                            class="inline-flex items-center rounded-full border border-[#1a4b35]/15 px-5 py-2 font-medium text-[#153926] transition hover:border-[#1a4b35]/30 hover:bg-white/70"
                        >
                            Dashboard
                        </a>
                        <template v-else>
                            <a
                                :href="loginUrl"
                                class="inline-flex items-center rounded-full border border-transparent px-4 py-2 font-medium text-[#153926] transition hover:border-[#1a4b35]/15 hover:bg-white/70"
                            >
                                Log in
                            </a>
                            <a
                                v-if="canRegister"
                                :href="registerUrl"
                                class="inline-flex items-center rounded-full bg-[#15513a] px-5 py-2 font-semibold text-white transition hover:bg-[#0f3f2d]"
                            >
                                Become a seller
                            </a>
                        </template>
                    </div>
                </nav>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div
                    class="absolute inset-x-0 top-0 h-[520px] bg-[linear-gradient(180deg,_rgba(255,248,232,0.9),_rgba(247,243,234,0.65)),radial-gradient(circle_at_top,_rgba(255,217,127,0.28),_transparent_40%)]"
                />
                <div
                    class="relative mx-auto max-w-6xl px-6 py-16 lg:px-8 lg:py-24"
                >
                    <div
                        class="grid gap-12 lg:grid-cols-[1.15fr_0.85fr] lg:items-center"
                    >
                        <div class="max-w-3xl">
                            <p
                                class="inline-flex rounded-full border border-[#d9c39c] bg-[#fff8ea] px-4 py-2 text-xs font-semibold tracking-[0.24em] text-[#8f5b20] uppercase"
                            >
                                Early merchant onboarding
                            </p>
                            <h2
                                class="mt-6 font-serif text-4xl leading-tight font-semibold text-[#163121] sm:text-5xl lg:text-6xl"
                            >
                                Put your business in front of customers before
                                everyone else catches up.
                            </h2>
                            <p
                                class="mt-6 max-w-2xl text-lg leading-8 text-[#4f6659]"
                            >
                                We are building a marketplace that helps local
                                merchants look more credible, get discovered
                                faster, and launch with a stronger digital
                                storefront from day one.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    v-if="canRegister"
                                    :href="registerUrl"
                                    class="inline-flex items-center justify-center rounded-full bg-[#15513a] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0f3f2d]"
                                >
                                    Apply as an early merchant
                                </a>
                                <a
                                    href="#merchant-benefits"
                                    class="inline-flex items-center justify-center rounded-full border border-[#1a4b35]/15 bg-white/80 px-6 py-3 text-sm font-semibold text-[#153926] transition hover:border-[#1a4b35]/35 hover:bg-white"
                                >
                                    Explore merchant benefits
                                </a>
                            </div>

                            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                                <div
                                    class="rounded-3xl border border-[#1a4b35]/10 bg-white/80 p-5 shadow-[0_18px_60px_rgba(19,49,33,0.08)]"
                                >
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-[#b36d27] uppercase"
                                    >
                                        Featured launch
                                    </p>
                                    <p
                                        class="mt-2 text-sm leading-6 text-[#446154]"
                                    >
                                        Early partners get better visibility
                                        while the marketplace is still curated.
                                    </p>
                                </div>
                                <div
                                    class="rounded-3xl border border-[#1a4b35]/10 bg-white/80 p-5 shadow-[0_18px_60px_rgba(19,49,33,0.08)]"
                                >
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-[#b36d27] uppercase"
                                    >
                                        Founder support
                                    </p>
                                    <p
                                        class="mt-2 text-sm leading-6 text-[#446154]"
                                    >
                                        We help you set up listings, polish your
                                        store profile, and launch confidently.
                                    </p>
                                </div>
                                <div
                                    class="rounded-3xl border border-[#1a4b35]/10 bg-white/80 p-5 shadow-[0_18px_60px_rgba(19,49,33,0.08)]"
                                >
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-[#b36d27] uppercase"
                                    >
                                        Faster credibility
                                    </p>
                                    <p
                                        class="mt-2 text-sm leading-6 text-[#446154]"
                                    >
                                        A polished merchant presence makes your
                                        business feel active, trusted, and
                                        ready.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div
                                class="absolute -inset-6 rounded-[32px] bg-[radial-gradient(circle_at_top,_rgba(255,216,117,0.45),_transparent_55%)] blur-2xl"
                            />
                            <div
                                class="relative overflow-hidden rounded-[32px] border border-[#1a4b35]/10 bg-[#12412d] p-8 text-white shadow-[0_30px_90px_rgba(14,41,29,0.2)]"
                            >
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-sm font-semibold tracking-[0.2em] text-[#f6d58e] uppercase"
                                    >
                                        Merchant offer
                                    </p>
                                    <span
                                        class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/75"
                                    >
                                        For early signups
                                    </span>
                                </div>

                                <div class="mt-8 space-y-5">
                                    <div class="rounded-2xl bg-white/6 p-5">
                                        <p class="text-lg font-semibold">
                                            Priority placement during launch
                                        </p>
                                        <p
                                            class="mt-2 text-sm leading-6 text-white/72"
                                        >
                                            Be one of the first stores visitors
                                            see when the marketplace starts
                                            driving real demand.
                                        </p>
                                    </div>
                                    <div class="rounded-2xl bg-white/6 p-5">
                                        <p class="text-lg font-semibold">
                                            Hands-on setup support
                                        </p>
                                        <p
                                            class="mt-2 text-sm leading-6 text-white/72"
                                        >
                                            We help shape your product listing,
                                            store details, and first impression
                                            so your page feels ready for
                                            customers.
                                        </p>
                                    </div>
                                    <div class="rounded-2xl bg-white/6 p-5">
                                        <p class="text-lg font-semibold">
                                            Stronger trust from day one
                                        </p>
                                        <p
                                            class="mt-2 text-sm leading-6 text-white/72"
                                        >
                                            Your store launches with a cleaner
                                            profile, clearer offer, and a more
                                            business-ready presentation.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="merchant-benefits"
                class="mx-auto max-w-6xl px-6 py-16 lg:px-8 lg:py-20"
            >
                <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
                        <p
                            class="text-sm font-semibold tracking-[0.24em] text-[#b36d27] uppercase"
                        >
                            Why businesses join early
                        </p>
                        <h3
                            class="mt-4 font-serif text-3xl font-semibold text-[#163121] sm:text-4xl"
                        >
                            Build momentum before the marketplace gets crowded.
                        </h3>
                        <p
                            class="mt-5 max-w-xl text-base leading-7 text-[#587062]"
                        >
                            Early adoption is not just about being first. It
                            gives your business more room to stand out, shape
                            your category presence, and build marketplace trust
                            ahead of later entrants.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-[28px] border border-[#1a4b35]/10 bg-white p-6 shadow-[0_16px_48px_rgba(19,49,33,0.06)]"
                        >
                            <p class="text-lg font-semibold text-[#163121]">
                                Get discovered earlier
                            </p>
                            <p class="mt-3 text-sm leading-6 text-[#567062]">
                                Join while categories are still forming and your
                                store has more room to be noticed.
                            </p>
                        </div>
                        <div
                            class="rounded-[28px] border border-[#1a4b35]/10 bg-white p-6 shadow-[0_16px_48px_rgba(19,49,33,0.06)]"
                        >
                            <p class="text-lg font-semibold text-[#163121]">
                                Launch with confidence
                            </p>
                            <p class="mt-3 text-sm leading-6 text-[#567062]">
                                Use the platform as a polished storefront that
                                makes your business feel established.
                            </p>
                        </div>
                        <div
                            class="rounded-[28px] border border-[#1a4b35]/10 bg-white p-6 shadow-[0_16px_48px_rgba(19,49,33,0.06)]"
                        >
                            <p class="text-lg font-semibold text-[#163121]">
                                Move faster with guidance
                            </p>
                            <p class="mt-3 text-sm leading-6 text-[#567062]">
                                We can help shape listings and merchant setup so
                                you are not starting from scratch.
                            </p>
                        </div>
                        <div
                            class="rounded-[28px] border border-[#1a4b35]/10 bg-white p-6 shadow-[0_16px_48px_rgba(19,49,33,0.06)]"
                        >
                            <p class="text-lg font-semibold text-[#163121]">
                                Look more credible online
                            </p>
                            <p class="mt-3 text-sm leading-6 text-[#567062]">
                                A stronger digital presence helps buyers trust
                                your store before they ever message you.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-[#1c4f38]/10 bg-white/70">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-8 text-sm text-[#587062] lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <p>
                    Built for merchants who want a stronger digital first
                    impression.
                </p>
                <div class="flex items-center gap-4">
                    <a
                        v-if="canRegister"
                        :href="registerUrl"
                        class="font-semibold text-[#15513a] transition hover:text-[#0f3f2d]"
                    >
                        Apply as a seller
                    </a>
                    <a
                        :href="loginUrl"
                        class="font-semibold text-[#15513a] transition hover:text-[#0f3f2d]"
                    >
                        Merchant login
                    </a>
                </div>
            </div>
        </footer>

        <MerchantPromoPopup :can-register="canRegister" />
    </div>
</template>
