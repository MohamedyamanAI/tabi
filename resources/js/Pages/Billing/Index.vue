<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
import { XMarkIcon } from '@heroicons/vue/16/solid';
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import BillingOverview from '@/Components/Billing/BillingOverview.vue';
import SeatManagement from '@/Components/Billing/SeatManagement.vue';
import PlanSelector from '@/Components/Billing/PlanSelector.vue';
import type { Organization } from '@/types/models';
import type { BillingState } from '@/types/billing';

const props = defineProps<{
    organization: Organization;
    plans: Record<string, string>;
}>();

const page = usePage<{ billing: BillingState }>();

const billing = computed(() => page.props.billing);

/** Paid orgs cannot decrease seats below the current subscription; only increases. */
const minSeats = computed(() => {
    const usage = Math.max(1, billing.value.used_seats);
    if (!billing.value.has_subscription) {
        return usage;
    }
    return Math.max(usage, billing.value.seat_count || 0);
});

const canUpdateSeats = computed(() => billing.value.has_subscription === true);

const effectivePaidSeats = computed(() => {
    if (!billing.value.has_subscription) return 1;
    return Math.max(1, billing.value.seat_count);
});

const loading = ref(false);
const errorMessage = ref<string | null>(null);
const checkoutSuccessVisible = ref(false);

function getAxiosErrorMessage(error: unknown, fallback: string): string {
    if (typeof error === 'object' && error !== null && 'response' in error) {
        const data = (error as { response?: { data?: { error?: string } } }).response?.data;
        if (data?.error && typeof data.error === 'string') {
            return data.error;
        }
    }
    return fallback;
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('checkout') === 'success') {
        checkoutSuccessVisible.value = true;
        params.delete('checkout');
        const next = params.toString();
        const path = window.location.pathname;
        window.history.replaceState({}, '', next ? `${path}?${next}` : path);
    }
});

function dismissCheckoutSuccess() {
    checkoutSuccessVisible.value = false;
}

async function checkout(productKey: string, seats: number) {
    loading.value = true;
    errorMessage.value = null;

    try {
        const response = await axios.post(route('billing.checkout'), {
            product_key: productKey,
            seats,
        });

        const url = response.data?.url as string | undefined;
        if (url) {
            window.location.href = url;
            return;
        }

        errorMessage.value = 'Checkout URL missing from response.';
    } catch (e) {
        errorMessage.value = getAxiosErrorMessage(e, 'Failed to create checkout.');
    } finally {
        loading.value = false;
    }
}

async function updateSeats(seats: number) {
    loading.value = true;
    errorMessage.value = null;

    try {
        await axios.post(route('billing.seats'), { seats });
        window.location.href = '/billing';
    } catch (e) {
        errorMessage.value = getAxiosErrorMessage(e, 'Failed to update seat count.');
    } finally {
        loading.value = false;
    }
}

async function swap(productKey: string) {
    loading.value = true;
    errorMessage.value = null;

    try {
        await axios.post(route('billing.swap'), { product_key: productKey });
        window.location.href = '/billing';
    } catch (e) {
        errorMessage.value = getAxiosErrorMessage(e, 'Failed to swap plan.');
    } finally {
        loading.value = false;
    }
}

async function cancel() {
    loading.value = true;
    errorMessage.value = null;

    try {
        await axios.post(route('billing.cancel'));
        window.location.href = '/billing';
    } catch (e) {
        errorMessage.value = getAxiosErrorMessage(e, 'Failed to cancel subscription.');
    } finally {
        loading.value = false;
    }
}

async function resume() {
    loading.value = true;
    errorMessage.value = null;

    try {
        await axios.post(route('billing.resume'));
        window.location.href = '/billing';
    } catch (e) {
        errorMessage.value = getAxiosErrorMessage(e, 'Failed to resume subscription.');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AppLayout title="Billing">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <PageTitle :icon="CreditCardIcon" title="Billing" />
                <p class="mt-1.5 text-sm text-text-secondary pl-7 sm:pl-[1.875rem]">
                    {{ organization.name }}
                </p>
            </div>
        </MainContainer>

        <div class="relative" data-testid="billing_view">
            <div
                v-if="loading"
                class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 rounded-lg bg-default-background/75 backdrop-blur-[1px]"
                aria-busy="true"
                aria-live="polite">
                <LoadingSpinner class="h-8 w-8 text-primary" />
                <span class="text-sm font-medium text-text-secondary">Processing…</span>
            </div>

            <MainContainer class="py-6 sm:py-8">
                <div class="mx-auto max-w-6xl space-y-8">
                    <div
                        v-if="checkoutSuccessVisible"
                        class="flex items-start justify-between gap-3 rounded-lg border border-emerald-600/25 bg-emerald-600/10 px-4 py-3 text-sm text-emerald-900 dark:text-emerald-100">
                        <p>
                            Checkout completed. Your subscription will update shortly if it is not
                            already active.
                        </p>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-emerald-800 hover:bg-emerald-600/15 dark:text-emerald-200"
                            aria-label="Dismiss"
                            @click="dismissCheckoutSuccess">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="rounded-lg border border-red-600/20 bg-red-600/10 px-4 py-3 text-sm text-red-800">
                        {{ errorMessage }}
                    </div>

                    <section id="billing-overview" class="scroll-mt-6">
                        <BillingOverview :billing="billing" />
                    </section>

                    <section id="billing-seats" class="scroll-mt-6">
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                            Seats
                        </h2>
                        <SeatManagement
                            :billing="billing"
                            :effectivePaidSeats="effectivePaidSeats"
                            :minSeats="minSeats"
                            :canUpdateSeats="canUpdateSeats"
                            :loading="loading"
                            @updateSeats="updateSeats" />
                    </section>

                    <section id="billing-plans" class="scroll-mt-6">
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                            Plans & pricing
                        </h2>
                        <PlanSelector
                            :billing="billing"
                            :plans="plans"
                            :minSeats="minSeats"
                            :loading="loading"
                            @checkout="checkout"
                            @swap="swap"
                            @cancel="cancel"
                            @resume="resume" />
                    </section>
                </div>
            </MainContainer>
        </div>
    </AppLayout>
</template>
