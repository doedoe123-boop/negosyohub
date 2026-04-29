<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        canRegister?: boolean;
        registerUrl?: string;
        benefitsHref?: string;
    }>(),
    {
        canRegister: true,
        registerUrl: '/register/sector',
        benefitsHref: '#merchant-benefits',
    },
);

const emit = defineEmits<{
    close: [];
}>();

const storageKey = 'merchant-promo-dismissed-at';
const isVisible = ref<boolean>(false);
const openTimer = ref<number | null>(null);

const applyLink = computed(() =>
    props.canRegister ? props.registerUrl : props.benefitsHref,
);

const rememberDismissal = (): void => {
    window.localStorage.setItem(storageKey, new Date().toISOString());
};

const closePopup = (): void => {
    isVisible.value = false;
    rememberDismissal();
    emit('close');
};

const shouldShowPopup = (): boolean => {
    const dismissedAt = window.localStorage.getItem(storageKey);

    if (!dismissedAt) {
        return true;
    }

    const dismissedTime = new Date(dismissedAt).getTime();

    if (Number.isNaN(dismissedTime)) {
        return true;
    }

    return Date.now() - dismissedTime > 1000 * 60 * 60 * 24 * 7;
};

const openPopup = (): void => {
    if (!shouldShowPopup() || isVisible.value) {
        return;
    }

    isVisible.value = true;
};

const handleScroll = (): void => {
    const scrollHeight =
        document.documentElement.scrollHeight - window.innerHeight;

    if (scrollHeight <= 0) {
        return;
    }

    if (window.scrollY / scrollHeight >= 0.35) {
        openPopup();
    }
};

onMounted(() => {
    if (!shouldShowPopup()) {
        return;
    }

    openTimer.value = window.setTimeout(() => {
        openPopup();
    }, 4500);

    window.addEventListener('scroll', handleScroll, { passive: true });
});

onBeforeUnmount(() => {
    if (openTimer.value !== null) {
        window.clearTimeout(openTimer.value);
    }

    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-3 opacity-0"
    >
        <div
            v-if="isVisible"
            class="fixed inset-x-0 bottom-4 z-50 px-4 sm:bottom-6 sm:px-6"
        >
            <div
                class="mx-auto max-w-2xl overflow-hidden rounded-[28px] border bg-[color:var(--color-surface)] text-[color:var(--color-text)] shadow-[var(--shadow-elevated)]"
                style="border-color: color-mix(in srgb, var(--color-border) 82%, white 18%)"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(249,93,47,0.16),_transparent_40%),linear-gradient(135deg,rgba(15,32,68,0.98),rgba(6,14,31,0.96))]"
                />
                <div class="relative p-6 sm:p-8">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="mb-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold tracking-[0.2em] uppercase"
                                style="background-color: rgba(249, 93, 47, 0.14); color: #ffd8cb"
                            >
                                Early Merchant Access
                            </p>
                            <h2
                                class="max-w-xl text-2xl leading-tight font-semibold text-white sm:text-3xl"
                            >
                                Own a business? Get listed before public launch.
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="rounded-full border p-2 text-white/70 transition hover:text-white"
                            style="border-color: rgba(255, 255, 255, 0.16)"
                            @click="closePopup"
                        >
                            <span class="sr-only">Close popup</span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <p
                        class="max-w-xl text-sm leading-6 text-slate-200 sm:text-base"
                    >
                        We are onboarding our first local merchants now. Early
                        partners get priority placement, launch support, and a
                        faster path to becoming one of the featured stores on
                        day one.
                    </p>

                    <div
                        class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <a
                            :href="applyLink"
                            class="inline-flex items-center justify-center rounded-full bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-600"
                            @click="closePopup"
                        >
                            Apply as a merchant
                        </a>
                        <a
                            :href="benefitsHref"
                            class="inline-flex items-center justify-center rounded-full border px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/6"
                            style="border-color: rgba(255, 255, 255, 0.2)"
                            @click="closePopup"
                        >
                            See merchant benefits
                        </a>
                    </div>

                    <p
                        class="mt-4 text-xs tracking-[0.18em] text-slate-300/70 uppercase"
                    >
                        Limited early-partner slots. No long-term commitment
                        required.
                    </p>
                </div>
            </div>
        </div>
    </transition>
</template>
