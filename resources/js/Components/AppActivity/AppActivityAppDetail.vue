<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import { formatDurationHoursMinutesSeconds } from '@/packages/ui/src/utils/time';
import { LoadingSpinner } from '@/packages/ui/src';
import type { AppActivityRow } from '@/Components/AppActivity/ActivityLogTable.vue';

const props = defineProps<{
    opened: boolean;
    appName: string;
    organizationId: string;
    start: string;
    end: string;
    memberId: string;
    canFilterByMember: boolean;
}>();

async function apiFetchJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
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
    });
    if (!response.ok) {
        throw new Error('Request failed');
    }
    return response.json();
}

const { data, isLoading } = useQuery({
    queryKey: computed(() => [
        'appActivityAppDetail',
        props.organizationId,
        props.appName,
        props.start,
        props.end,
        props.memberId,
    ]),
    queryFn: async () => {
        const params = new URLSearchParams({
            start: props.start,
            end: props.end,
            sort: 'duration_seconds',
            direction: 'desc',
            per_page: '100',
            app_name: props.appName,
        });
        if (props.memberId && props.canFilterByMember) {
            params.set('member_id', props.memberId);
        }
        const url = `/api/v1/organizations/${props.organizationId}/app-activities?${params.toString()}`;
        return apiFetchJson<{ data: AppActivityRow[] }>(url);
    },
    enabled: computed(() => props.opened && !!props.organizationId && !!props.appName),
});

const rows = computed(() => data.value?.data ?? []);

function labelFor(row: AppActivityRow): string {
    if (row.url) {
        try {
            return new URL(row.url).hostname;
        } catch {
            return row.url;
        }
    }
    return row.window_title || '—';
}
</script>

<template>
    <div class="py-3 px-4 border-t border-card-border bg-card-background/50">
        <div v-if="isLoading" class="flex justify-center py-6">
            <LoadingSpinner />
        </div>
        <div v-else-if="!rows.length" class="text-sm text-muted-foreground text-center py-4">
            No segments for this app.
        </div>
        <div v-else class="space-y-2">
            <div
                v-for="row in rows"
                :key="row.id"
                class="flex justify-between items-start gap-3 text-sm">
                <div class="flex-1 min-w-0 break-all text-text-primary">
                    <span class="text-text-secondary">{{ labelFor(row) }}</span>
                    <span
                        v-if="row.window_title && labelFor(row) !== row.window_title"
                        class="block text-xs text-muted-foreground mt-0.5 truncate">
                        {{ row.window_title }}
                    </span>
                </div>
                <div class="shrink-0 text-right space-y-0.5">
                    <div class="text-xs font-medium text-text-secondary tabular-nums">
                        {{ formatDurationHoursMinutesSeconds(row.duration_seconds) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
