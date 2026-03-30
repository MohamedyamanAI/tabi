<script lang="ts" setup>
import VChart, { THEME_KEY } from 'vue-echarts';
import { provide, computed, inject, ref, type ComputedRef } from 'vue';
import { use } from 'echarts/core';
import { useElementSize } from '@vueuse/core';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { BoltIcon } from '@heroicons/vue/20/solid';
import { HeatmapChart } from 'echarts/charts';
import {
    CalendarComponent,
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import {
    firstDayIndex,
    formatDate,
    formatHumanReadableDuration,
    getDayJsInstance,
} from '@/packages/ui/src/utils/time';
import chroma from 'chroma-js';
import { useCssVariable } from '@/utils/useCssVariable';
import { useQuery } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api, type Organization } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';

const organization = inject<ComputedRef<Organization>>('organization');

const organizationId = computed(() => getCurrentOrganizationId());

const heatmapMode = ref<'hours' | 'activity'>('hours');

const { data: dailyHoursTracked, isLoading: isLoadingHours } = useQuery({
    queryKey: ['dailyTrackedHours', organizationId],
    queryFn: () => {
        return api.dailyTrackedHours({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});

const { data: dailyActivityLevels, isLoading: isLoadingActivity } = useQuery({
    queryKey: ['dailyActivityLevels', organizationId],
    queryFn: () =>
        api.dailyActivityLevels({
            params: {
                organization: organizationId.value!,
            },
        }),
    enabled: computed(
        () => !!organizationId.value && !!organization?.value?.activity_tracking_enabled
    ),
});

const isLoading = computed(() =>
    heatmapMode.value === 'hours' ? isLoadingHours.value : isLoadingActivity.value
);

const showModeToggle = computed(() => !!organization?.value?.activity_tracking_enabled);

use([
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
    CalendarComponent,
    HeatmapChart,
    CanvasRenderer,
]);

provide(THEME_KEY, 'dark');

const maxHours = computed(() => {
    if (!isLoadingHours.value && dailyHoursTracked.value) {
        return Math.max(Math.max(...dailyHoursTracked.value.map((el) => el.duration)), 1);
    }
    return 1;
});

const backgroundColor = useCssVariable('--theme-color-card-background');
const borderColor = useCssVariable('--color-border');
const labelColor = useCssVariable('--color-text-secondary');
const chartColorRaw = useCssVariable('--theme-color-chart');

const chartEmptyColorRaw = useCssVariable('--color-bg-tertiary');
const chartEmptyColor = computed(() => {
    if (!chartEmptyColorRaw.value) return '#2a2c32';
    return chroma(chartEmptyColorRaw.value).hex();
});
const chartColor = computed(() => {
    if (!chartColorRaw.value) return '#bae6fd';
    return `rgb(${chartColorRaw.value})`;
});

const chartContainer = ref<HTMLElement | null>(null);
const { width: containerWidth } = useElementSize(chartContainer);

const numberOfWeeks = computed(() => {
    const availableWidth = containerWidth.value || 400;
    const minCellSize = 25;
    const labelSpace = 80;
    const usableWidth = availableWidth - labelSpace;
    const maxWeeks = Math.floor(usableWidth / minCellSize);
    return Math.max(4, Math.min(12, maxWeeks));
});

const dateRange = computed(() => {
    const today = getDayJsInstance()();
    const startOfWeek = today.startOf('week');
    const rangeStart = startOfWeek.subtract(numberOfWeeks.value - 1, 'week');
    return [today.format('YYYY-MM-DD'), rangeStart.format('YYYY-MM-DD')];
});

const heatmapSeriesData = computed(() => {
    if (heatmapMode.value === 'hours') {
        return dailyHoursTracked?.value?.map((el) => [el.date, el.duration]) ?? [];
    }
    return dailyActivityLevels?.value?.map((el) => [el.date, el.activity_level ?? 0]) ?? [];
});

const activityMax = computed(() => {
    const rows = dailyActivityLevels.value ?? [];
    if (rows.length === 0) return 100;
    const m = Math.max(...rows.map((r) => r.activity_level ?? 0), 1);
    return Math.min(100, m);
});

const effectiveMax = computed(() =>
    heatmapMode.value === 'hours' ? maxHours.value : Math.max(activityMax.value, 1)
);

const option = computed(() => {
    const maxVal = effectiveMax.value;
    const empty = chartEmptyColor.value;
    const bright = chartColor.value;

    const pieces =
        heatmapMode.value === 'hours'
            ? [
                  { value: 0, color: empty },
                  {
                      gt: 0,
                      lte: maxVal * 0.25,
                      color: chroma.mix(empty, bright, 0.3).hex(),
                  },
                  {
                      gt: maxVal * 0.25,
                      lte: maxVal * 0.5,
                      color: chroma.mix(empty, bright, 0.6).hex(),
                  },
                  {
                      gt: maxVal * 0.5,
                      lte: maxVal * 0.75,
                      color: chroma.mix(empty, bright, 0.8).hex(),
                  },
                  { gt: maxVal * 0.75, lte: maxVal, color: bright },
              ]
            : [
                  { value: 0, color: empty },
                  {
                      gt: 0,
                      lte: 25,
                      color: chroma.mix(empty, bright, 0.3).hex(),
                  },
                  {
                      gt: 25,
                      lte: 50,
                      color: chroma.mix(empty, bright, 0.55).hex(),
                  },
                  {
                      gt: 50,
                      lte: 75,
                      color: chroma.mix(empty, bright, 0.75).hex(),
                  },
                  { gt: 75, lte: 100, color: bright },
              ];

    return {
        tooltip: {},
        visualMap: {
            type: 'piecewise',
            orient: 'horizontal',
            left: 'center',
            top: 'center',
            pieces,
            show: false,
        },
        calendar: {
            top: 35,
            bottom: 20,
            left: 35,
            right: 5,
            cellSize: 'auto',
            orient: 'horizontal',
            dayLabel: {
                firstDay: firstDayIndex.value,
                color: labelColor.value,
                fontFamily: 'Inter, sans-serif',
            },
            monthLabel: {
                color: labelColor.value,
                fontFamily: 'Inter, sans-serif',
            },
            splitLine: {
                show: false,
            },
            range: dateRange.value,
            itemStyle: {
                color: 'transparent',
                borderWidth: 8,
                borderColor: backgroundColor.value,
            },
            yearLabel: { show: false },
        },
        series: {
            type: 'heatmap',
            coordinateSystem: 'calendar',
            data: heatmapSeriesData.value,
            itemStyle: {
                borderRadius: 5,
                borderColor: borderColor.value,
                borderWidth: 1,
            },
            tooltip: {
                valueFormatter: (value: number, dataIndex: number) => {
                    const dateStr = heatmapSeriesData.value[dataIndex]?.[0];
                    const dateLabel = formatDate(
                        typeof dateStr === 'string' ? dateStr : '',
                        organization?.value?.date_format
                    );
                    if (heatmapMode.value === 'hours') {
                        if (dailyHoursTracked?.value) {
                            return (
                                dateLabel +
                                ': ' +
                                formatHumanReadableDuration(
                                    value,
                                    organization?.value?.interval_format,
                                    organization?.value?.number_format
                                )
                            );
                        }
                        return '';
                    }
                    return `${dateLabel} — Activity: ${Math.round(value)}%`;
                },
            },
        },
        backgroundColor: 'transparent',
    };
});

const chartReady = computed(() => {
    if (heatmapMode.value === 'hours') {
        return dailyHoursTracked.value !== undefined;
    }
    return dailyActivityLevels.value !== undefined;
});
</script>

<template>
    <DashboardCard title="Activity Graph" :icon="BoltIcon">
        <template v-if="showModeToggle" #actions>
            <div class="flex rounded-md border border-card-border bg-card-background text-xs font-medium">
                <button
                    type="button"
                    class="px-2 py-1 rounded-l-md transition-colors"
                    :class="
                        heatmapMode === 'hours'
                            ? 'bg-card-background text-text-primary border-r border-card-border'
                            : 'text-text-secondary hover:text-text-primary'
                    "
                    @click="heatmapMode = 'hours'">
                    Hours
                </button>
                <button
                    type="button"
                    class="px-2 py-1 rounded-r-md transition-colors"
                    :class="
                        heatmapMode === 'activity'
                            ? 'bg-card-background text-text-primary'
                            : 'text-text-secondary hover:text-text-primary'
                    "
                    @click="heatmapMode = 'activity'">
                    Activity
                </button>
            </div>
        </template>
        <div class="px-2">
            <div v-if="isLoading" class="flex justify-center items-center h-40">
                <LoadingSpinner />
            </div>
            <div v-else-if="chartReady" ref="chartContainer">
                <v-chart
                    class="chart"
                    :autoresize="true"
                    :option="option"
                    style="height: 260px; background-color: transparent" />
            </div>
            <div v-else class="text-center text-gray-500 py-8">No activity data available</div>
        </div>
    </DashboardCard>
</template>

<style></style>
