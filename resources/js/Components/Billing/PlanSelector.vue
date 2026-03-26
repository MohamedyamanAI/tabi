<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/packages/ui/src/Buttons';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import type { BillingState } from '@/types/billing';

const props = defineProps<{
    billing: BillingState;
    plans: Record<string, string>;
    minSeats: number;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'checkout', product_key: string, seats: number): void;
    (e: 'swap', product_key: string): void;
    (e: 'cancel'): void;
    (e: 'resume'): void;
}>();

/** Polar keeps status active with cancel_at_period_end until period end. */
const isScheduledToCancel = computed(
    () => props.billing.cancel_at_period_end === true && props.billing.has_subscription === true
);

const periodEndLabel = computed(() => {
    if (!props.billing.current_period_ends_at) {
        return null;
    }
    return getDayJsInstance()(props.billing.current_period_ends_at).format('YYYY-MM-DD');
});

const cycle = ref<'monthly' | 'annual'>(
    props.billing.billing_cycle === 'annual' ? 'annual' : 'monthly'
);
const seats = ref<number>(props.minSeats);

watch(
    () => props.minSeats,
    (next) => {
        seats.value = Math.max(seats.value, next);
    }
);

watch(
    () => props.billing.billing_cycle,
    (next) => {
        if (next === 'annual') cycle.value = 'annual';
        if (next === 'monthly') cycle.value = 'monthly';
    }
);

const monitorProductKey = computed(() => (cycle.value === 'annual' ? 'monitor_annual' : 'monitor_monthly'));
const trackProductKey = computed(() =>
    cycle.value === 'annual' ? 'track_annual' : 'track_monthly'
);

const isCurrentCycle = computed(() => {
    if (!props.billing.billing_cycle) return false;
    return props.billing.billing_cycle === cycle.value;
});

const isCurrentTrack = computed(() => {
    return props.billing.has_subscription && props.billing.tier === 'track' && isCurrentCycle.value;
});

const isCurrentMonitor = computed(() => {
    return props.billing.has_subscription && props.billing.tier === 'monitor' && isCurrentCycle.value;
});

function submitCheckout(productKey: string) {
    emit('checkout', productKey, seats.value);
}

function submitSwap(productKey: string) {
    emit('swap', productKey);
}
</script>

<template>
    <div class="rounded-xl border border-border-secondary bg-background px-6 py-6 sm:px-8">
        <div class="mb-6 flex flex-col gap-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                        Plans & pricing
                    </div>
                    <h3 class="mt-1 text-xl font-semibold text-text-primary">
                        Choose a plan for your team
                    </h3>
                    <p v-if="billing.is_blocked" class="mt-2 text-sm text-red-600">
                        Your organization is blocked. Upgrade your plan or reduce members to continue
                        tracking time.
                    </p>
                </div>
                <div
                    v-if="!billing.has_subscription"
                    class="text-sm text-text-secondary">
                    Checkout seats:
                    <span class="font-semibold text-text-primary">{{ seats }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="inline-flex items-center gap-1 rounded-full border border-border-secondary/70 bg-card-background p-1 text-xs text-text-secondary">
                    <span class="hidden pl-2 font-semibold sm:inline">Billing cycle</span>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="
                            cycle === 'monthly'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-text-primary hover:bg-background/80'
                        "
                        @click="cycle = 'monthly'">
                        Monthly
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="
                            cycle === 'annual'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-text-primary hover:bg-background/80'
                        "
                        @click="cycle = 'annual'">
                        Annual
                    </button>
                    <span
                        v-if="cycle === 'annual'"
                        class="pr-2 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                        Save up to 67%
                    </span>
                </div>

                <div v-if="!billing.has_subscription" class="min-w-[180px] flex-1">
                    <label class="block text-xs font-medium text-text-secondary">Seats</label>
                    <input
                        v-model.number="seats"
                        type="number"
                        min="1"
                        :placeholder="String(minSeats)"
                        class="mt-1 block w-full rounded-md border border-input-border bg-input-background px-3 py-2 text-sm text-text-primary shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                        :disabled="loading" />
                    <div class="mt-1 text-[11px] text-text-tertiary">Minimum seats: {{ minSeats }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3 md:items-stretch">
            <!-- Free -->
            <div
                class="flex flex-col justify-between rounded-xl border border-border-secondary/80 bg-card-background p-5">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                        Free
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-bold tracking-tight text-text-primary">$0</span>
                        <span class="text-sm text-text-secondary">/month</span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-text-secondary">
                        Ideal for solo use and trying out the basics.
                    </p>
                    <ul class="mt-4 space-y-2 text-xs text-text-secondary">
                        <li class="flex gap-2">
                            <span class="text-primary">✓</span>
                            <span>1 real (non-placeholder) member</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-primary">✓</span>
                            <span>Core time tracking & projects</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-primary">✓</span>
                            <span>Tasks, clients, tags, reporting</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-text-tertiary">—</span>
                            <span>No screenshots</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Track -->
            <div
                class="flex flex-col justify-between rounded-xl border p-5"
                :class="isCurrentTrack ? ‘border-primary ring-1 ring-primary/20’ : ‘border-border-secondary bg-card-background’">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                            Track
                        </div>
                        <span
                            v-if="isCurrentTrack"
                            class="inline-flex shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">
                            Current
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-bold tracking-tight text-text-primary">{{
                            cycle === ‘monthly’ ? ‘$3’ : ‘$12’
                        }}</span>
                        <span class="text-sm text-text-secondary"
                            >/member/{{ cycle === ‘monthly’ ? ‘mo’ : ‘yr’ }}</span
                        >
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-text-secondary">
                        Full productivity tracking without screenshots.
                    </p>
                    <ul class="mt-4 space-y-2 text-xs text-text-secondary">
                        <li class="flex gap-2">
                            <span class="text-primary">✓</span>
                            <span>Time tracking & projects</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-primary">✓</span>
                            <span>Clients, tags, reporting, import/export</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-text-tertiary">—</span>
                            <span>No screenshots</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-6">
                    <Button
                        v-if="!billing.has_subscription"
                        variant="outline"
                        class="w-full"
                        :disabled="loading || seats < minSeats"
                        @click="submitCheckout(trackProductKey)">
                        Start Track trial
                    </Button>
                    <Button
                        v-else-if="billing.tier === ‘monitor’"
                        variant="outline"
                        class="w-full"
                        :disabled="loading || isCurrentTrack"
                        @click="submitSwap(trackProductKey)">
                        Downgrade to Track
                    </Button>
                    <template v-else-if="isCurrentTrack">
                        <p
                            v-if="isScheduledToCancel"
                            class="text-center text-[11px] text-amber-800 dark:text-amber-200">
                            <template v-if="periodEndLabel">
                                Subscription ends on {{ periodEndLabel }}. Resume to keep Track.
                            </template>
                            <template v-else> Subscription is scheduled to cancel. Resume to keep Track. </template>
                        </p>
                        <p v-else class="text-center text-[11px] text-text-tertiary">
                            You’re on Track ({{ cycle }}).
                        </p>
                        <Button
                            v-if="isScheduledToCancel"
                            variant="outline"
                            class="mt-2 w-full border-emerald-600/35 text-emerald-800 hover:bg-emerald-600/10 dark:text-emerald-200"
                            :disabled="loading"
                            @click="emit(‘resume’)">
                            Resume subscription
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            class="mt-2 w-full border-red-600/30 text-red-700 hover:bg-red-600/10"
                            :disabled="loading"
                            @click="emit(‘cancel’)">
                            Cancel at period end
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Monitor -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-xl border p-5 shadow-sm"
                :class="
                    isCurrentMonitor
                        ? ‘border-primary bg-accent-600/5 ring-1 ring-primary/25’
                        : ‘border-accent-600/30 bg-gradient-to-b from-accent-600/5 to-card-background’
                ">
                <div
                    v-if="!isCurrentMonitor"
                    class="absolute right-3 top-3 rounded-full bg-accent-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                    Recommended
                </div>
                <div>
                    <div class="flex items-start justify-between gap-2" :class="!isCurrentMonitor ? ‘pr-14’ : ‘’">
                        <div class="text-xs font-semibold uppercase tracking-wide text-accent-600">
                            Monitor
                        </div>
                        <span
                            v-if="isCurrentMonitor"
                            class="inline-flex shrink-0 rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-primary-foreground">
                            Current
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-bold tracking-tight text-text-primary">{{
                            cycle === ‘monthly’ ? ‘$5’ : ‘$36’
                        }}</span>
                        <span class="text-sm text-text-secondary"
                            >/member/{{ cycle === ‘monthly’ ? ‘mo’ : ‘yr’ }}</span
                        >
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-text-secondary">
                        Everything in Track plus screenshot capture & management.
                    </p>
                    <ul class="mt-4 space-y-2 text-xs text-text-secondary">
                        <li class="flex gap-2">
                            <span class="text-accent-600">✓</span>
                            <span>Everything in Track</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-accent-600">✓</span>
                            <span>Screenshot capture & management</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-6">
                    <Button
                        v-if="!billing.has_subscription"
                        class="w-full"
                        :disabled="loading || seats < minSeats"
                        @click="submitCheckout(monitorProductKey)">
                        Start Monitor trial
                    </Button>
                    <Button
                        v-else-if="billing.tier === ‘track’"
                        class="w-full"
                        :disabled="loading || isCurrentMonitor"
                        @click="submitSwap(monitorProductKey)">
                        Upgrade to Monitor
                    </Button>
                    <template v-else-if="isCurrentMonitor">
                        <p
                            v-if="isScheduledToCancel"
                            class="text-center text-[11px] text-amber-800 dark:text-amber-200">
                            <template v-if="periodEndLabel">
                                Subscription ends on {{ periodEndLabel }}. Resume to keep Monitor.
                            </template>
                            <template v-else> Subscription is scheduled to cancel. Resume to keep Monitor. </template>
                        </p>
                        <p v-else class="text-center text-[11px] text-text-tertiary">
                            You’re on Monitor ({{ cycle }}).
                        </p>
                        <Button
                            v-if="isScheduledToCancel"
                            variant="outline"
                            class="mt-2 w-full border-emerald-600/35 text-emerald-800 hover:bg-emerald-600/10 dark:text-emerald-200"
                            :disabled="loading"
                            @click="emit(‘resume’)">
                            Resume subscription
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            class="mt-2 w-full border-red-600/30 text-red-700 hover:bg-red-600/10"
                            :disabled="loading"
                            @click="emit(‘cancel’)">
                            Cancel at period end
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
