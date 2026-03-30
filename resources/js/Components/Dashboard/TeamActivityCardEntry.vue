<script lang="ts" setup>
import { computed } from 'vue';
import { activityLevelBarClass, activityLevelTextClass } from '@/utils/activityLevel';

const props = defineProps<{
    name: string;
    description: string | null;
    working?: boolean;
    showActivityBar?: boolean;
    activityLevel?: number | null;
    avgKeystrokesPerMin?: number;
    avgMouseClicksPerMin?: number;
}>();

const tooltipTitle = computed(() => {
    if (
        props.avgKeystrokesPerMin === undefined ||
        props.avgMouseClicksPerMin === undefined
    ) {
        return undefined;
    }
    return `Keystrokes: ${props.avgKeystrokesPerMin}/min avg, Mouse clicks: ${props.avgMouseClicksPerMin}/min avg`;
});

const barWidth = computed(() => {
    if (props.activityLevel === null || props.activityLevel === undefined) {
        return 0;
    }
    return Math.min(100, Math.max(0, props.activityLevel));
});

const offline = computed(
    () =>
        props.showActivityBar &&
        (props.activityLevel === null || props.activityLevel === undefined)
);
</script>

<template>
    <div class="px-3.5 py-2 2xl:py-3">
        <div class="col-span-2">
            <div class="flex justify-between">
                <p
                    class="text-xs min-w-0 overflow-ellipsis overflow-hidden flex-1 text-text-secondary">
                    {{ name }}
                </p>
                <div v-if="working" class="flex space-x-1.5 items-center justify-end">
                    <span class="relative flex h-3 w-3 justify-center items-center">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-green-500 font-medium text-sm block pb-0.5"> working </span>
                </div>
            </div>
            <div
                class="text-text-secondary text-sm font-medium text-ellipsis whitespace-nowrap max-w-full overflow-hidden">
                {{ description }}
            </div>
            <div v-if="showActivityBar" class="mt-2 flex items-center gap-2" :title="tooltipTitle">
                <div class="h-1.5 flex-1 rounded-full bg-tertiary min-w-0">
                    <div
                        v-if="!offline"
                        class="h-full rounded-full transition-all"
                        :class="activityLevelBarClass(activityLevel ?? null)"
                        :style="{ width: barWidth + '%' }"></div>
                </div>
                <span
                    v-if="offline"
                    class="text-xs text-muted whitespace-nowrap shrink-0">
                    offline
                </span>
                <span
                    v-else
                    class="text-xs font-medium whitespace-nowrap shrink-0 tabular-nums w-9 text-right"
                    :class="activityLevelTextClass(activityLevel ?? null)">
                    {{ activityLevel }}%
                </span>
            </div>
        </div>
    </div>
</template>
