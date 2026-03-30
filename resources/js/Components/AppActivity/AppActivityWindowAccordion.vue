<script setup lang="ts">
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/packages/ui/src';
import { computed, ref } from 'vue';
import { formatDurationHoursMinutesSeconds } from '@/packages/ui/src/utils/time';
import AppActivityAppDetail from '@/Components/AppActivity/AppActivityAppDetail.vue';

const props = defineProps<{
    rows: { app_name: string; total_seconds: number }[];
    organizationId: string;
    start: string;
    end: string;
    memberId: string;
    canFilterByMember: boolean;
}>();

const openApps = ref<string[]>([]);

const totalSeconds = computed(() => props.rows.reduce((sum, r) => sum + r.total_seconds, 0));

function pct(seconds: number): number {
    if (totalSeconds.value <= 0) return 0;
    return (seconds / totalSeconds.value) * 100;
}

function initial(name: string): string {
    return name.trim().charAt(0).toUpperCase() || '?';
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-0.5">
            <h2 class="text-base font-medium text-text-primary">Window activity</h2>
            <p class="text-sm text-text-secondary">
                Total time:
                <span class="text-text-primary font-medium tabular-nums">{{
                    formatDurationHoursMinutesSeconds(totalSeconds)
                }}</span>
            </p>
        </div>

        <div
            v-if="!rows.length"
            class="text-sm text-muted-foreground py-12 text-center rounded-lg border border-card-border bg-card-background">
            No app activity in this range.
        </div>

        <Accordion v-else v-model="openApps" type="multiple" collapsible class="space-y-2.5">
            <AccordionItem
                v-for="row in rows"
                :key="row.app_name"
                :value="row.app_name"
                class="!border-0 rounded-lg border border-card-border bg-card-background overflow-hidden">
                <AccordionTrigger
                    class="hover:no-underline px-3 py-3 [&[data-state=open]>div>.chevron]:rotate-180">
                    <div class="w-full pr-2">
                        <div class="flex justify-between items-center gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div
                                    class="shrink-0 w-10 h-10 rounded-md bg-tertiary flex items-center justify-center text-sm font-semibold text-text-secondary">
                                    {{ initial(row.app_name) }}
                                </div>
                                <div class="flex flex-col flex-1 gap-1.5 items-start min-w-0">
                                    <h3 class="font-semibold text-text-primary truncate text-left w-full">
                                        {{ row.app_name }}
                                    </h3>
                                    <div class="w-full bg-quaternary rounded-full h-2 overflow-hidden">
                                        <div
                                            class="bg-accent-400 h-2 rounded-full min-h-2 transition-all"
                                            :style="{ width: `${Math.min(pct(row.total_seconds), 100)}%` }"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end shrink-0 gap-0.5 min-w-[4.5rem]">
                                <span class="text-sm font-medium text-text-secondary tabular-nums">
                                    {{ pct(row.total_seconds).toFixed(1) }}%
                                </span>
                                <span class="text-xs text-muted-foreground tabular-nums">
                                    {{ formatDurationHoursMinutesSeconds(row.total_seconds) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <template #icon>
                        <svg
                            class="chevron h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="pb-0">
                    <AppActivityAppDetail
                        :opened="openApps.includes(row.app_name)"
                        :app-name="row.app_name"
                        :organization-id="organizationId"
                        :start="start"
                        :end="end"
                        :member-id="memberId"
                        :can-filter-by-member="canFilterByMember" />
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    </div>
</template>
