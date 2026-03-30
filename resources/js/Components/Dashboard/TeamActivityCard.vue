<script lang="ts" setup>
import { useQuery } from '@tanstack/vue-query';
import { computed, inject, type ComputedRef } from 'vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import TeamActivityCardEntry from '@/Components/Dashboard/TeamActivityCardEntry.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api, type Organization } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';
import { router } from '@inertiajs/vue3';
import { canViewMembers } from '@/utils/permissions';

const organization = inject<ComputedRef<Organization>>('organization');

const organizationId = computed(() => getCurrentOrganizationId());

const { data: latestTeamActivity, isLoading } = useQuery({
    queryKey: ['latestTeamActivity', organizationId],
    queryFn: () => {
        return api.latestTeamActivity({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});

const { data: teamActivityLevels } = useQuery({
    queryKey: ['teamActivityLevels', organizationId],
    queryFn: () =>
        api.teamActivityLevels({
            params: {
                organization: organizationId.value!,
            },
        }),
    enabled: computed(
        () =>
            !!organizationId.value &&
            !!organization?.value?.activity_tracking_enabled &&
            canViewMembers()
    ),
});

const levelByMemberId = computed(() => {
    const map = new Map<
        string,
        {
            activity_level: number;
            avg_keystrokes_per_min: number;
            avg_mouse_clicks_per_min: number;
        }
    >();
    teamActivityLevels.value?.forEach((row) => {
        map.set(row.member_id, {
            activity_level: row.activity_level,
            avg_keystrokes_per_min: row.avg_keystrokes_per_min,
            avg_mouse_clicks_per_min: row.avg_mouse_clicks_per_min,
        });
    });
    return map;
});
</script>

<template>
    <DashboardCard title="Team Activity" :icon="UserGroupIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else-if="latestTeamActivity">
            <TeamActivityCardEntry
                v-for="activity in latestTeamActivity"
                :key="activity.time_entry_id"
                :class="latestTeamActivity.length === 4 ? 'last:border-0' : ''"
                :name="activity.name"
                :description="activity.description"
                :working="activity.status"
                :show-activity-bar="!!organization?.activity_tracking_enabled"
                :activity-level="levelByMemberId.get(activity.member_id)?.activity_level ?? null"
                :avg-keystrokes-per-min="
                    levelByMemberId.get(activity.member_id)?.avg_keystrokes_per_min
                "
                :avg-mouse-clicks-per-min="
                    levelByMemberId.get(activity.member_id)?.avg_mouse_clicks_per_min
                "></TeamActivityCardEntry>
        </div>
        <div v-else class="text-center text-gray-500 py-8">No team activity found</div>
        <div
            v-if="latestTeamActivity && latestTeamActivity.length <= 1"
            :class="latestTeamActivity?.length === 1 ? 'pb-5' : 'py-5'"
            class="text-center flex flex-1 justify-center items-center">
            <div>
                <UserGroupIcon class="w-8 text-icon-default inline pb-2"></UserGroupIcon>
                <h3 class="text-text-primary font-semibold text-sm">Invite your co-workers</h3>
                <p class="pb-5 text-sm">You can invite your entire team.</p>
                <SecondaryButton @click="router.visit(route('members'))"
                    >Go to Members
                </SecondaryButton>
            </div>
        </div>
    </DashboardCard>
</template>
