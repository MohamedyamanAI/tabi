<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { formatDurationHoursMinutesSeconds } from '@/packages/ui/src/utils/time';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
    LoadingSpinner,
} from '@/packages/ui/src';
import { computed } from 'vue';
import { useVueTable, getCoreRowModel, type ColumnDef } from '@tanstack/vue-table';

export interface AppActivityRow {
    id: string;
    timestamp: string;
    app_name: string;
    window_title: string;
    url: string | null;
    duration_seconds: number;
}

export type AppActivitySortColumn = 'timestamp' | 'app_name' | 'window_title' | 'duration_seconds';

const props = defineProps<{
    rows: AppActivityRow[];
    isLoading: boolean;
    currentPage: number;
    lastPage: number;
    sortColumn: AppActivitySortColumn;
    sortDirection: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    prev: [];
    next: [];
    sort: [column: AppActivitySortColumn];
}>();

function formatTime(iso: string) {
    return new Date(iso).toLocaleString();
}

const columns = computed<ColumnDef<AppActivityRow>[]>(() => [
    { id: 'timestamp', accessorKey: 'timestamp', header: 'Time' },
    { id: 'app_name', accessorKey: 'app_name', header: 'App' },
    { id: 'window_title', accessorKey: 'window_title', header: 'Window' },
    { id: 'url', accessorKey: 'url', header: 'URL' },
    { id: 'duration_seconds', accessorKey: 'duration_seconds', header: 'Duration' },
]);

const table = useVueTable({
    get data() {
        return props.rows;
    },
    get columns() {
        return columns.value;
    },
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
});

function headerIndicator(columnId: AppActivitySortColumn) {
    if (props.sortColumn !== columnId) {
        return '';
    }
    return props.sortDirection === 'asc' ? ' \u2191' : ' \u2193';
}
</script>

<template>
    <Accordion type="single" collapsible default-value="activity-log" class="w-full">
        <AccordionItem
            value="activity-log"
            class="!border-0 rounded-lg border border-card-border bg-card-background overflow-hidden">
            <AccordionTrigger
                class="hover:no-underline px-3 py-3 text-sm font-semibold text-text-primary [&[data-state=open]>svg]:rotate-180">
                Activity log
            </AccordionTrigger>
            <AccordionContent class="!pb-0 border-t border-card-border">
                <div class="px-3 pb-3">
                    <div v-if="isLoading" class="flex justify-center py-12">
                        <LoadingSpinner />
                    </div>
                    <div
                        v-else-if="!rows.length"
                        class="text-sm text-muted-foreground py-8 text-center">
                        No records.
                    </div>
                    <div v-else class="overflow-x-auto rounded-lg border border-card-border">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-card-background border-b border-card-border text-text-secondary">
                                <tr>
                                    <th class="px-3 py-2 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-0.5 hover:text-text-primary text-left"
                                            @click="emit('sort', 'timestamp')">
                                            Time<span class="tabular-nums text-muted-foreground">{{
                                                headerIndicator('timestamp')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-0.5 hover:text-text-primary text-left"
                                            @click="emit('sort', 'app_name')">
                                            App<span class="tabular-nums text-muted-foreground">{{
                                                headerIndicator('app_name')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 font-medium min-w-[140px]">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-0.5 hover:text-text-primary text-left"
                                            @click="emit('sort', 'window_title')">
                                            Window<span class="tabular-nums text-muted-foreground">{{
                                                headerIndicator('window_title')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 font-medium min-w-[120px]">URL</th>
                                    <th class="px-3 py-2 font-medium text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-0.5 hover:text-text-primary justify-end w-full"
                                            @click="emit('sort', 'duration_seconds')">
                                            Duration<span class="tabular-nums text-muted-foreground">{{
                                                headerIndicator('duration_seconds')
                                            }}</span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in table.getRowModel().rows"
                                    :key="row.original.id"
                                    class="border-b border-card-border last:border-0 hover:bg-card-background/80">
                                    <td class="px-3 py-2 text-text-secondary whitespace-nowrap">
                                        {{ formatTime(row.original.timestamp) }}
                                    </td>
                                    <td class="px-3 py-2 text-text-primary whitespace-nowrap">
                                        {{ row.original.app_name }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-text-secondary max-w-xs truncate"
                                        :title="row.original.window_title">
                                        {{ row.original.window_title }}
                                    </td>
                                    <td class="px-3 py-2 max-w-[200px] truncate">
                                        <a
                                            v-if="row.original.url"
                                            :href="row.original.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-primary hover:underline">
                                            {{ row.original.url }}
                                        </a>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td
                                        class="px-3 py-2 text-right tabular-nums text-text-primary whitespace-nowrap">
                                        {{
                                            formatDurationHoursMinutesSeconds(
                                                row.original.duration_seconds
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="lastPage > 1" class="flex justify-center gap-2 mt-4">
                        <SecondaryButton size="small" :disabled="currentPage <= 1" @click="emit('prev')">
                            Previous
                        </SecondaryButton>
                        <span class="text-sm text-text-secondary self-center px-2">
                            {{ currentPage }} / {{ lastPage }}
                        </span>
                        <SecondaryButton
                            size="small"
                            :disabled="currentPage >= lastPage"
                            @click="emit('next')">
                            Next
                        </SecondaryButton>
                    </div>
                </div>
            </AccordionContent>
        </AccordionItem>
    </Accordion>
</template>
