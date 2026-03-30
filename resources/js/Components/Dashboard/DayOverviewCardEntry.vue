<script setup lang="ts">
import DayOverviewCardChart from '@/Components/Dashboard/DayOverviewCardChart.vue';
import { formatHumanReadableDate, formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import { inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';
import { activityLevelTextClass } from '@/utils/activityLevel';

const organization = inject<ComputedRef<Organization>>('organization');

const props = defineProps<{
    date: string;
    duration: number;
    history: number[];
    showActivityLevel?: boolean;
    activityLevel?: number | null;
}>();

function activityLabel() {
    if (props.activityLevel === null || props.activityLevel === undefined) {
        return '—';
    }
    return `${props.activityLevel}%`;
}
</script>

<template>
    <div class="px-3.5 py-2 flex justify-between @container">
        <div class="flex items-center min-w-[70px]">
            <p class="text-sm text-text-primary">
                {{ formatHumanReadableDate(date) }}
            </p>
        </div>
        <div class="items-center justify-center flex-1 hidden @2xs:flex">
            <DayOverviewCardChart :history="history"></DayOverviewCardChart>
        </div>
        <div class="flex text-sm items-center justify-center text-text-secondary min-w-[65px]">
            {{
                formatHumanReadableDuration(
                    duration,
                    organization?.interval_format,
                    organization?.number_format
                )
            }}
        </div>
        <div
            v-if="showActivityLevel"
            class="flex text-xs font-medium items-center justify-end min-w-[40px] tabular-nums"
            :class="activityLevelTextClass(activityLevel ?? null)">
            {{ activityLabel() }}
        </div>
    </div>
</template>
