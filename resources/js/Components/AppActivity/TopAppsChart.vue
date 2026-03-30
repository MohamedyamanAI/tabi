<script setup lang="ts">
import { computed } from 'vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import type { Organization } from '@/packages/api/src';
import chroma from 'chroma-js';
import { useCssVariable } from '@/utils/useCssVariable';

const props = defineProps<{
    rows: { app_name: string; total_seconds: number }[];
    organization: Organization | null | undefined;
}>();

const chartColorRaw = useCssVariable('--theme-color-chart');

const topRows = computed(() => props.rows.slice(0, 10));

const otherSeconds = computed(() =>
    props.rows.slice(10).reduce((sum, r) => sum + r.total_seconds, 0)
);

const maxSeconds = computed(() => {
    const candidates = [...topRows.value.map((r) => r.total_seconds)];
    if (otherSeconds.value > 0) {
        candidates.push(otherSeconds.value);
    }
    if (!candidates.length) return 1;
    return Math.max(...candidates, 1);
});

const topRowCount = computed(() => topRows.value.length);

function barColor(index: number): string {
    const base = chartColorRaw.value ? `rgb(${chartColorRaw.value})` : '#38bdf8';
    const t = topRowCount.value > 1 ? index / (topRowCount.value - 1) : 0;
    return chroma.mix('#64748b', base, t).hex();
}

const otherBarColor = '#64748b';
</script>

<template>
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-text-primary">Top apps</h3>
        <div v-if="!rows.length" class="text-sm text-muted-foreground py-6 text-center">
            No app activity in this range.
        </div>
        <div v-else class="space-y-2.5">
            <div v-for="(row, i) in topRows" :key="row.app_name" class="flex items-center gap-3 text-sm">
                <div class="w-36 shrink-0 truncate text-text-secondary" :title="row.app_name">
                    {{ row.app_name }}
                </div>
                <div class="flex-1 min-w-0 h-2 rounded-full bg-tertiary overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all"
                        :style="{
                            width: `${Math.round((row.total_seconds / maxSeconds) * 100)}%`,
                            backgroundColor: barColor(i),
                        }"></div>
                </div>
                <div class="w-24 shrink-0 text-right tabular-nums text-text-primary">
                    {{
                        formatHumanReadableDuration(
                            row.total_seconds,
                            organization?.interval_format,
                            organization?.number_format
                        )
                    }}
                </div>
            </div>
            <div v-if="otherSeconds > 0" class="flex items-center gap-3 text-sm">
                <div class="w-36 shrink-0 truncate text-text-secondary" title="All other applications">
                    Other
                </div>
                <div class="flex-1 min-w-0 h-2 rounded-full bg-tertiary overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all"
                        :style="{
                            width: `${Math.round((otherSeconds / maxSeconds) * 100)}%`,
                            backgroundColor: otherBarColor,
                        }"></div>
                </div>
                <div class="w-24 shrink-0 text-right tabular-nums text-text-primary">
                    {{
                        formatHumanReadableDuration(
                            otherSeconds,
                            organization?.interval_format,
                            organization?.number_format
                        )
                    }}
                </div>
            </div>
        </div>
    </div>
</template>
