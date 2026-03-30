<script setup lang="ts">
import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
    LoadingSpinner,
} from '@/packages/ui/src';
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import { formatDurationHoursMinutesSeconds } from '@/packages/ui/src/utils/time';

const props = defineProps<{
    timeEntryId: string;
    organizationId: string;
    /** Time entry start (ISO UTC from API) */
    timeEntryStart: string;
    /** Null while timer is running */
    timeEntryEnd: string | null;
}>();

interface ActivitySummary {
    app_name: string;
    total_seconds: number;
    session_count: number;
}

/** Laravel AppActivitySummaryRequest expects Y-m-d\TH:i:s\Z (no fractional seconds). */
function toApiUtcZ(iso: string): string {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        const fallback = new Date();
        return formatUtcParts(fallback);
    }
    return formatUtcParts(d);
}

function formatUtcParts(d: Date): string {
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, '0');
    const day = String(d.getUTCDate()).padStart(2, '0');
    const h = String(d.getUTCHours()).padStart(2, '0');
    const min = String(d.getUTCMinutes()).padStart(2, '0');
    const s = String(d.getUTCSeconds()).padStart(2, '0');
    return `${y}-${m}-${day}T${h}:${min}:${s}Z`;
}

const rangeForApi = computed(() => {
    const start = toApiUtcZ(props.timeEntryStart);
    const endRaw = props.timeEntryEnd ?? new Date().toISOString();
    let end = toApiUtcZ(endRaw);
    if (new Date(end).getTime() < new Date(start).getTime()) {
        end = start;
    }
    return { start, end };
});

async function fetchActivitySummary() {
    const params = new URLSearchParams({
        start: rangeForApi.value.start,
        end: rangeForApi.value.end,
        time_entry_id: props.timeEntryId,
    });
    const response = await fetch(
        `/api/v1/organizations/${props.organizationId}/app-activities/summary?${params.toString()}`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN':
                    decodeURIComponent(
                        document.cookie
                            .split('; ')
                            .find((row) => row.startsWith('XSRF-TOKEN='))
                            ?.split('=')[1] ?? ''
                    ),
            },
        }
    );
    if (! response.ok) {
        throw new Error('Failed to fetch activity');
    }
    return response.json();
}

const { data, isLoading, error } = useQuery<{ data: ActivitySummary[] }>({
    queryKey: computed(() => [
        'timeEntryActivity',
        props.timeEntryId,
        props.organizationId,
        rangeForApi.value.start,
        rangeForApi.value.end,
    ]),
    queryFn: fetchActivitySummary,
});

const activities = computed<ActivitySummary[]>(() => data.value?.data ?? []);
const totalSeconds = computed(() =>
    activities.value.reduce((sum: number, a: ActivitySummary) => sum + a.total_seconds, 0)
);

function pct(seconds: number): number {
    if (totalSeconds.value <= 0) return 0;
    return (seconds / totalSeconds.value) * 100;
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md p-1.5 text-text-tertiary hover:bg-tertiary hover:text-text-primary transition-colors"
                title="View app activity for this session">
                <ChartBarIcon class="h-4 w-4" />
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-0 overflow-hidden" align="end">
            <div class="p-3 border-b border-default-background-separator bg-tertiary/30">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    App Activity
                </h4>
            </div>

            <div v-if="isLoading" class="p-8 flex justify-center">
                <LoadingSpinner size="small" />
            </div>

            <div v-else-if="error" class="p-4 text-center text-xs text-negative">
                Failed to load activity.
            </div>

            <div v-else-if="activities.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                No app activity recorded for this session.
            </div>

            <div v-else class="max-h-64 overflow-y-auto p-2 space-y-1">
                <div
                    v-for="activity in activities"
                    :key="activity.app_name"
                    class="group relative flex flex-col p-2 rounded-md hover:bg-tertiary/50 transition-colors">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-xs font-medium text-text-primary truncate pr-2">
                            {{ activity.app_name }}
                        </span>
                        <span class="text-[10px] tabular-nums text-text-secondary font-medium">
                            {{ formatDurationHoursMinutesSeconds(activity.total_seconds) }}
                        </span>
                    </div>
                    <div class="w-full bg-quaternary h-1 rounded-full overflow-hidden">
                        <div
                            class="bg-accent-400 h-full min-h-1 rounded-full transition-all duration-500"
                            :style="{ width: `${Math.min(pct(activity.total_seconds), 100)}%` }"></div>
                    </div>
                </div>
            </div>

            <div
                v-if="activities.length > 0"
                class="p-2 border-t border-default-background-separator bg-tertiary/10 text-center">
                <span class="text-[10px] text-muted-foreground uppercase font-medium">
                    Total tracked: {{ formatDurationHoursMinutesSeconds(totalSeconds) }}
                </span>
            </div>
        </PopoverContent>
    </Popover>
</template>
