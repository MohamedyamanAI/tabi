<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import MemberCombobox from '@/Components/Common/Member/MemberCombobox.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { ComputerDesktopIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { isBillingActivated, isBlocked } from '@/utils/billing';
import { canManageBilling, canViewAllScreenshots, canViewAppActivities } from '@/utils/permissions';
import { Link } from '@inertiajs/vue3';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import AppActivityWindowAccordion from '@/Components/AppActivity/AppActivityWindowAccordion.vue';
import ActivityLogTable, {
    type AppActivityRow,
    type AppActivitySortColumn,
} from '@/Components/AppActivity/ActivityLogTable.vue';
import { LoadingSpinner } from '@/packages/ui/src';

const selectedMemberId = ref('');
const logPage = ref(1);
const logSortColumn = ref<AppActivitySortColumn>('timestamp');
const logSortDirection = ref<'asc' | 'desc'>('desc');

function setLogSort(column: AppActivitySortColumn) {
    if (logSortColumn.value === column) {
        logSortDirection.value = logSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        logSortColumn.value = column;
        logSortDirection.value = ['app_name', 'window_title'].includes(column) ? 'asc' : 'desc';
    }
    logPage.value = 1;
}

type DatePreset = 'today' | 'week' | 'month';
const activePreset = ref<DatePreset>('today');

function getStartOfWeek(date: Date): Date {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    d.setDate(diff);
    d.setHours(0, 0, 0, 0);
    return d;
}

function getStartOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function toISODate(date: Date): string {
    return date.toISOString().replace(/\.\d{3}Z$/, 'Z');
}

const startDate = computed(() => {
    const now = new Date();
    if (activePreset.value === 'today') {
        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return toISODate(start);
    }
    if (activePreset.value === 'week') {
        return toISODate(getStartOfWeek(now));
    }
    return toISODate(getStartOfMonth(now));
});

const endDate = computed(() => {
    const now = new Date();
    if (activePreset.value === 'today') {
        const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
        return toISODate(end);
    }
    if (activePreset.value === 'week') {
        const start = getStartOfWeek(now);
        const end = new Date(start);
        end.setDate(end.getDate() + 6);
        end.setHours(23, 59, 59);
        return toISODate(end);
    }
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
    return toISODate(end);
});

function setPreset(preset: DatePreset) {
    activePreset.value = preset;
    logPage.value = 1;
}

function clearMemberFilter() {
    selectedMemberId.value = '';
    logPage.value = 1;
}

const organizationId = computed(() => getCurrentOrganizationId());
const { organization, isLoading: isOrgLoading } = useOrganizationQuery(getCurrentOrganizationId()!);

const canAccessPage = computed(
    () =>
        !isBlocked() &&
        !!organization.value?.activity_tracking_enabled &&
        !!organization.value?.app_activity_sync_enabled &&
        canViewAppActivities()
);

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

const summaryQueryKey = computed(() => [
    'appActivitySummary',
    organizationId.value,
    selectedMemberId.value,
    startDate.value,
    endDate.value,
]);

const { data: summaryData, isLoading: summaryLoading } = useQuery({
    queryKey: summaryQueryKey,
    queryFn: async () => {
        const params = new URLSearchParams({
            start: startDate.value,
            end: endDate.value,
        });
        if (selectedMemberId.value && canViewAllScreenshots()) {
            params.set('member_id', selectedMemberId.value);
        }
        const url = `/api/v1/organizations/${organizationId.value}/app-activities/summary?${params.toString()}`;
        return apiFetchJson<{ data: { app_name: string; total_seconds: number; session_count: number }[] }>(
            url
        );
    },
    enabled: computed(() => !!organizationId.value && !isOrgLoading.value && canAccessPage.value),
});

const logQueryKey = computed(() => [
    'appActivities',
    organizationId.value,
    selectedMemberId.value,
    startDate.value,
    endDate.value,
    logPage.value,
    logSortColumn.value,
    logSortDirection.value,
]);

const { data: logData, isLoading: logLoading } = useQuery({
    queryKey: logQueryKey,
    queryFn: async () => {
        const params = new URLSearchParams({
            start: startDate.value,
            end: endDate.value,
            page: String(logPage.value),
            sort: logSortColumn.value,
            direction: logSortDirection.value,
        });
        if (selectedMemberId.value && canViewAllScreenshots()) {
            params.set('member_id', selectedMemberId.value);
        }
        const url = `/api/v1/organizations/${organizationId.value}/app-activities?${params.toString()}`;
        return apiFetchJson<{
            data: AppActivityRow[];
            meta: { current_page: number; last_page: number };
        }>(url);
    },
    enabled: computed(() => !!organizationId.value && !isOrgLoading.value && canAccessPage.value),
});

const summaryRows = computed(
    () =>
        summaryData.value?.data.map((r) => ({
            app_name: r.app_name,
            total_seconds: r.total_seconds,
        })) ?? []
);

const logRows = computed(() => logData.value?.data ?? []);
const logMeta = computed(() => logData.value?.meta);

function logPrev() {
    if (logPage.value > 1) logPage.value--;
}

function logNext() {
    const last = logMeta.value?.last_page ?? 1;
    if (logPage.value < last) logPage.value++;
}
</script>

<template>
    <AppLayout title="App Activity" data-testid="app_activity_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <PageTitle :icon="ComputerDesktopIcon" title="App Activity" />
        </MainContainer>

        <div v-if="isOrgLoading" class="flex justify-center py-24">
            <LoadingSpinner />
        </div>

        <template v-else-if="!canAccessPage">
            <MainContainer class="py-16 text-center max-w-md mx-auto">
                <p v-if="isBlocked()" class="text-text-secondary">
                    This workspace is blocked. Resolve billing to use app activity.
                </p>
                <p v-else-if="!organization?.app_activity_sync_enabled" class="text-text-secondary">
                    Enable <strong>activity level tracking</strong> and <strong>app activity sync</strong> under
                    organization settings to use this page.
                </p>
                <p v-else class="text-text-secondary">You do not have access to app activity.</p>
                <Link
                    v-if="isBillingActivated() && canManageBilling() && isBlocked()"
                    href="/billing">
                    <PrimaryButton type="button" class="mt-6 px-4 py-2 text-sm">
                        <span class="inline-flex items-center gap-2">
                            <CreditCardIcon class="w-4 h-4" />
                            <span>Manage billing</span>
                        </span>
                    </PrimaryButton>
                </Link>
            </MainContainer>
        </template>

        <template v-else>
            <MainContainer class="py-4 border-b border-default-background-separator">
                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="canViewAllScreenshots()" class="w-56">
                        <MemberCombobox v-model="selectedMemberId" />
                    </div>
                    <SecondaryButton
                        v-if="canViewAllScreenshots() && selectedMemberId"
                        size="small"
                        @click="clearMemberFilter">
                        Clear member
                    </SecondaryButton>
                    <div class="flex items-center gap-1 ml-auto">
                        <SecondaryButton
                            size="small"
                            :class="activePreset === 'today' ? 'bg-card-background-active' : ''"
                            @click="setPreset('today')">
                            Today
                        </SecondaryButton>
                        <SecondaryButton
                            size="small"
                            :class="activePreset === 'week' ? 'bg-card-background-active' : ''"
                            @click="setPreset('week')">
                            This week
                        </SecondaryButton>
                        <SecondaryButton
                            size="small"
                            :class="activePreset === 'month' ? 'bg-card-background-active' : ''"
                            @click="setPreset('month')">
                            This month
                        </SecondaryButton>
                    </div>
                </div>
            </MainContainer>

            <MainContainer class="py-6 space-y-10 max-w-5xl">
                <div v-if="summaryLoading" class="flex justify-center py-12">
                    <LoadingSpinner />
                </div>
                <AppActivityWindowAccordion
                    v-else
                    :rows="summaryRows"
                    :organization-id="organizationId!"
                    :start="startDate"
                    :end="endDate"
                    :member-id="selectedMemberId"
                    :can-filter-by-member="canViewAllScreenshots()" />

                <div class="mt-10">
                    <ActivityLogTable
                        :rows="logRows"
                        :is-loading="logLoading"
                        :current-page="logMeta?.current_page ?? 1"
                        :last-page="logMeta?.last_page ?? 1"
                        :sort-column="logSortColumn"
                        :sort-direction="logSortDirection"
                        @prev="logPrev"
                        @next="logNext"
                        @sort="setLogSort" />
                </div>
            </MainContainer>
        </template>
    </AppLayout>
</template>
