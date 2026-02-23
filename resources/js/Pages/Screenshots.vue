<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CameraIcon } from '@heroicons/vue/20/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import ScreenshotGallery from '@/Components/Common/Screenshot/ScreenshotGallery.vue';
import MemberCombobox from '@/Components/Common/Member/MemberCombobox.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { canViewAllScreenshots } from '@/utils/permissions';
import { ref, computed } from 'vue';

const selectedMemberId = ref('');

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
    } else if (activePreset.value === 'week') {
        return toISODate(getStartOfWeek(now));
    } else {
        return toISODate(getStartOfMonth(now));
    }
});

const endDate = computed(() => {
    const now = new Date();
    if (activePreset.value === 'today') {
        const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
        return toISODate(end);
    } else if (activePreset.value === 'week') {
        const start = getStartOfWeek(now);
        const end = new Date(start);
        end.setDate(end.getDate() + 6);
        end.setHours(23, 59, 59);
        return toISODate(end);
    } else {
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
        return toISODate(end);
    }
});

function setPreset(preset: DatePreset) {
    activePreset.value = preset;
}

function clearMemberFilter() {
    selectedMemberId.value = '';
}
</script>

<template>
    <AppLayout title="Screenshots" data-testid="screenshots_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <PageTitle :icon="CameraIcon" title="Screenshots" />
        </MainContainer>
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
                        This Week
                    </SecondaryButton>
                    <SecondaryButton
                        size="small"
                        :class="activePreset === 'month' ? 'bg-card-background-active' : ''"
                        @click="setPreset('month')">
                        This Month
                    </SecondaryButton>
                </div>
            </div>
        </MainContainer>
        <MainContainer class="pt-6">
            <ScreenshotGallery
                :member-id="selectedMemberId || undefined"
                :start-date="startDate"
                :end-date="endDate" />
        </MainContainer>
    </AppLayout>
</template>
