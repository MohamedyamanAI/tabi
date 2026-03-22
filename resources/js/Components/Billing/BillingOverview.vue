<script setup lang="ts">
import { computed } from 'vue';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import type { BillingState } from '@/types/billing';

const props = defineProps<{
    billing: BillingState;
}>();

const planLabel = computed(() => {
    if (!props.billing.has_subscription && !props.billing.has_trial) {
        return 'Free';
    }

    if (props.billing.tier === 'pro') {
        return props.billing.has_trial ? 'Pro Trial' : 'Pro';
    }

    if (props.billing.tier === 'standard') {
        return 'Standard';
    }

    return 'Paid';
});

const renewalLabel = computed(() => {
    if (!props.billing.current_period_ends_at) return null;
    return getDayJsInstance()(props.billing.current_period_ends_at).format('YYYY-MM-DD');
});

const trialLabel = computed(() => {
    if (!props.billing.has_trial || !props.billing.trial_until) return null;
    return getDayJsInstance()(props.billing.trial_until).format('YYYY-MM-DD');
});

const cycleLabel = computed(() => {
    if (!props.billing.billing_cycle) return '—';
    return props.billing.billing_cycle === 'annual' ? 'Annual' : 'Monthly';
});

const seatsStat = computed(() => {
    if (props.billing.has_subscription) {
        return `${props.billing.used_seats} / ${props.billing.seat_count || 1}`;
    }
    return `${props.billing.used_seats} (Free: 1 seat)`;
});

const periodStat = computed(() => {
    if (trialLabel.value) {
        return `Trial ends ${trialLabel.value}`;
    }
    if (
        props.billing.cancel_at_period_end &&
        props.billing.has_subscription &&
        renewalLabel.value
    ) {
        return `Cancels on ${renewalLabel.value}`;
    }
    if (renewalLabel.value) {
        return `Renews ${renewalLabel.value}`;
    }
    return '—';
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-border-secondary bg-gradient-to-br from-card-background to-background">
        <div class="border-b border-border-secondary/80 px-6 py-5 sm:px-8 sm:py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                        Subscription
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-2xl font-semibold tracking-tight text-text-primary">
                            {{ planLabel }}
                        </span>
                        <span
                            v-if="billing.billing_cycle"
                            class="inline-flex items-center rounded-full border border-border-secondary/70 bg-background/80 px-2.5 py-0.5 text-xs font-medium text-text-secondary">
                            {{ billing.billing_cycle === 'annual' ? 'Billed annually' : 'Billed monthly' }}
                        </span>
                    </div>
                    <p v-if="!billing.has_subscription && !billing.has_trial" class="text-sm text-text-secondary">
                        You’re on the Free plan with one member slot for non-placeholder users.
                    </p>
                    <p
                        v-else-if="
                            billing.cancel_at_period_end && billing.has_subscription && renewalLabel
                        "
                        class="text-sm text-amber-800 dark:text-amber-200">
                        Access continues until {{ renewalLabel }}. You can resume billing from Plans &amp; pricing
                        below.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <div
                        v-if="billing.is_blocked"
                        class="inline-flex items-center rounded-full border border-red-600/25 bg-red-600/10 px-3 py-1 text-xs font-semibold text-red-700">
                        Blocked
                    </div>
                    <div
                        v-else-if="billing.cancel_at_period_end && billing.has_subscription"
                        class="inline-flex items-center rounded-full border border-amber-600/35 bg-amber-600/10 px-3 py-1 text-xs font-semibold text-amber-900 dark:text-amber-200">
                        Scheduled to cancel
                    </div>
                    <div
                        v-else-if="billing.has_subscription || billing.has_trial"
                        class="inline-flex items-center rounded-full border border-emerald-600/20 bg-emerald-600/10 px-3 py-1 text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                        Active
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-px bg-border-secondary/60 sm:grid-cols-3">
            <div class="bg-background px-6 py-4 sm:px-8">
                <div class="text-xs font-medium text-text-tertiary">Members</div>
                <div class="mt-1 text-lg font-semibold tabular-nums text-text-primary">
                    {{ seatsStat }}
                </div>
            </div>
            <div class="bg-background px-6 py-4 sm:px-8">
                <div class="text-xs font-medium text-text-tertiary">Billing cycle</div>
                <div class="mt-1 text-lg font-semibold text-text-primary">
                    {{ cycleLabel }}
                </div>
            </div>
            <div class="bg-background px-6 py-4 sm:px-8">
                <div class="text-xs font-medium text-text-tertiary">Period</div>
                <div class="mt-1 text-sm font-semibold leading-snug text-text-primary">
                    {{ periodStat }}
                </div>
            </div>
        </div>
    </div>
</template>
