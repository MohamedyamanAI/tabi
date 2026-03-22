<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/packages/ui/src/Buttons';
import type { BillingState } from '@/types/billing';

const props = defineProps<{
    billing: BillingState;
    effectivePaidSeats: number;
    minSeats: number;
    canUpdateSeats: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'updateSeats', seats: number): void;
}>();

const seats = ref<number>(Math.max(props.effectivePaidSeats, props.minSeats));

watch(
    () => [props.effectivePaidSeats, props.minSeats],
    () => {
        seats.value = Math.max(props.effectivePaidSeats, props.minSeats);
    }
);

const usedPercent = computed(() => {
    const paid = Math.max(props.effectivePaidSeats, 1);
    const used = props.billing.used_seats;
    return Math.min(100, (used / paid) * 100);
});

const paidLabel = computed(() => {
    return props.canUpdateSeats ? `${props.effectivePaidSeats}` : '1';
});
</script>

<template>
    <div class="rounded-xl border border-border-secondary bg-background px-0 py-0">
        <div class="border-b border-border-secondary/80 px-6 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                        Seat usage
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums text-text-primary">
                        {{ billing.used_seats }}
                        <span class="text-lg font-medium text-text-tertiary">/</span>
                        {{ paidLabel }}
                    </div>
                    <div v-if="billing.is_blocked" class="mt-2 text-sm text-red-600">
                        Your organization is blocked because it has more members than your current
                        paid seats. Remove members or increase seats to continue.
                    </div>
                </div>

                <div
                    v-if="canUpdateSeats"
                    class="rounded-full border border-border-secondary/70 bg-card-background px-3 py-1 text-xs text-text-secondary">
                    Cycle:
                    <span class="font-medium text-text-primary">{{ billing.billing_cycle ?? 'monthly' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <div class="h-2 w-full overflow-hidden rounded-full bg-card-background">
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-300"
                        :style="{ width: `${usedPercent}%` }" />
                </div>
            </div>
        </div>

        <div class="border-t border-border-secondary/80 px-6 py-5" v-if="canUpdateSeats">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium text-text-secondary">Paid seats</label>
                    <input
                        v-model.number="seats"
                        type="number"
                        :min="minSeats"
                        :max="Math.max(1000, seats)"
                        :placeholder="String(minSeats)"
                        class="mt-2 block w-full rounded-md border border-input-border bg-input-background px-3 py-2 text-sm text-text-primary shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :disabled="loading" />
                    <div class="mt-1 text-xs text-text-tertiary">
                        Minimum seats: {{ minSeats }} (cannot decrease below your current plan)
                    </div>
                </div>

                <Button
                    class="w-full sm:w-auto sm:min-w-[9rem]"
                    :disabled="seats < minSeats || loading"
                    @click="emit('updateSeats', seats)">
                    Update seats
                </Button>
            </div>
        </div>

        <div class="border-t border-border-secondary/80 px-6 py-5" v-else>
            <p class="text-sm text-text-secondary">
                Subscribe to a paid plan to purchase multiple seats and manage billing here.
            </p>
        </div>
    </div>
</template>
