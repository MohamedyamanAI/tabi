<script setup lang="ts">
import { computed, ref } from 'vue';
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId, getCurrentRole } from '@/utils/useUser';
import ScreenshotThumbnail from './ScreenshotThumbnail.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps<{
    timeEntryId?: string;
    memberId?: string;
    startDate?: string;
    endDate?: string;
}>();

interface Screenshot {
    id: string;
    time_entry_id: string;
    member_id: string;
    captured_at: string;
    image_url: string;
    created_at: string | null;
    updated_at: string | null;
}

interface ScreenshotResponse {
    data: Screenshot[];
    links: Record<string, string | null>;
    meta: Record<string, unknown>;
}

const queryClient = useQueryClient();
const organizationId = computed(() => getCurrentOrganizationId());
const role = computed(() => getCurrentRole());

const canDelete = computed(() => {
    return ['owner', 'admin', 'manager'].includes(role.value ?? '');
});

const selectedScreenshot = ref<Screenshot | null>(null);
const showDeleteConfirm = ref(false);
const screenshotToDelete = ref<Screenshot | null>(null);

const queryParams = computed(() => {
    const params = new URLSearchParams();
    if (props.timeEntryId) {
        params.set('time_entry_id', props.timeEntryId);
    }
    if (props.memberId) {
        params.set('member_id', props.memberId);
    }
    if (props.startDate) {
        params.set('start', props.startDate);
    }
    if (props.endDate) {
        params.set('end', props.endDate);
    }
    return params.toString();
});

const queryKey = computed(() => [
    'screenshots',
    organizationId.value,
    props.timeEntryId,
    props.memberId,
    props.startDate,
    props.endDate,
]);

const { data: screenshotsData, isLoading } = useQuery<ScreenshotResponse>({
    queryKey,
    queryFn: async () => {
        const url = `/api/v1/organizations/${organizationId.value}/screenshots${queryParams.value ? '?' + queryParams.value : ''}`;
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
            throw new Error('Failed to fetch screenshots');
        }
        return response.json();
    },
    enabled: computed(() => !!organizationId.value),
});

const deleteMutation = useMutation({
    mutationFn: async (screenshotId: string) => {
        const response = await fetch(
            `/api/v1/organizations/${organizationId.value}/screenshots/${screenshotId}`,
            {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
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
        if (!response.ok) {
            throw new Error('Failed to delete screenshot');
        }
    },
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['screenshots'] });
        showDeleteConfirm.value = false;
        if (selectedScreenshot.value?.id === screenshotToDelete.value?.id) {
            selectedScreenshot.value = null;
        }
        screenshotToDelete.value = null;
    },
});

const screenshots = computed(() => screenshotsData.value?.data ?? []);

interface DateGroup {
    label: string;
    screenshots: Screenshot[];
}

const groupedScreenshots = computed<DateGroup[]>(() => {
    const groups = new Map<string, Screenshot[]>();
    for (const screenshot of screenshots.value) {
        const dateKey = screenshot.captured_at.substring(0, 10);
        if (!groups.has(dateKey)) {
            groups.set(dateKey, []);
        }
        groups.get(dateKey)!.push(screenshot);
    }
    return Array.from(groups.entries()).map(([dateKey, items]) => ({
        label: formatDateLabel(dateKey),
        screenshots: items,
    }));
});

function formatDateLabel(dateKey: string): string {
    const date = new Date(dateKey + 'T00:00:00');
    return date.toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatCapturedAt(dateStr: string): string {
    return new Date(dateStr).toLocaleString();
}

function openScreenshot(screenshot: Screenshot) {
    selectedScreenshot.value = screenshot;
}

function confirmDelete(screenshot: Screenshot) {
    screenshotToDelete.value = screenshot;
    showDeleteConfirm.value = true;
}

function executeDelete() {
    if (screenshotToDelete.value) {
        deleteMutation.mutate(screenshotToDelete.value.id);
    }
}
</script>

<template>
    <div>
        <div v-if="isLoading" class="text-center py-8 text-muted">Loading screenshots...</div>
        <div v-else-if="screenshots.length === 0" class="text-center py-8 text-muted">
            No screenshots available.
        </div>
        <div v-else>
            <div v-for="group in groupedScreenshots" :key="group.label" class="mb-6">
                <h3
                    class="text-sm font-semibold text-text-primary mb-3 pb-2 border-b border-default-background-separator">
                    {{ group.label }}
                </h3>
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <ScreenshotThumbnail
                        v-for="screenshot in group.screenshots"
                        :id="screenshot.id"
                        :key="screenshot.id"
                        :image-url="screenshot.image_url"
                        :captured-at="formatCapturedAt(screenshot.captured_at)"
                        :can-delete="canDelete"
                        @click="openScreenshot(screenshot)"
                        @delete="confirmDelete(screenshot)" />
                </div>
            </div>
        </div>

        <!-- Enlarged view modal -->
        <DialogModal :show="!!selectedScreenshot" max-width="5xl" @close="selectedScreenshot = null">
            <template #title>
                <div class="flex items-center justify-between w-full">
                    <span>
                        Screenshot -
                        {{
                            selectedScreenshot
                                ? formatCapturedAt(selectedScreenshot.captured_at)
                                : ''
                        }}
                    </span>
                    <button class="text-muted hover:text-white" @click="selectedScreenshot = null">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </template>
            <template #content>
                <img
                    v-if="selectedScreenshot"
                    :src="selectedScreenshot.image_url"
                    alt="Screenshot"
                    class="w-full rounded-lg" />
            </template>
            <template #footer>
                <SecondaryButton @click="selectedScreenshot = null"> Close </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Delete confirmation modal -->
        <DialogModal :show="showDeleteConfirm" @close="showDeleteConfirm = false">
            <template #title> Delete Screenshot </template>
            <template #content>
                Are you sure you want to delete this screenshot? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="showDeleteConfirm = false"> Cancel </SecondaryButton>
                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteMutation.isPending.value }"
                    :disabled="deleteMutation.isPending.value"
                    @click="executeDelete">
                    Delete
                </DangerButton>
            </template>
        </DialogModal>
    </div>
</template>
