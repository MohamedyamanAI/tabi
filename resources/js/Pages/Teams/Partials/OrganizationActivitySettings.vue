<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref, watch } from 'vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { Checkbox } from '@/packages/ui/src';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { isBlocked } from '@/utils/billing';

const store = useOrganizationStore();
const { updateOrganization } = store;
const { organization } = storeToRefs(store);
const queryClient = useQueryClient();

const form = ref({
    activity_tracking_enabled: false,
    app_activity_sync_enabled: false,
});

onMounted(() => {
    form.value.activity_tracking_enabled = organization.value?.activity_tracking_enabled ?? false;
    form.value.app_activity_sync_enabled = organization.value?.app_activity_sync_enabled ?? false;
});

watch(
    () => form.value.activity_tracking_enabled,
    (enabled) => {
        if (!enabled) {
            form.value.app_activity_sync_enabled = false;
        }
    }
);

const mutation = useMutation({
    mutationFn: (values: Partial<UpdateOrganizationBody>) => updateOrganization(values),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
});

async function submit() {
    await mutation.mutateAsync({
        activity_tracking_enabled: form.value.activity_tracking_enabled,
        app_activity_sync_enabled: form.value.app_activity_sync_enabled,
    });
}
</script>

<template>
    <FormSection>
        <template #title>Activity level tracking</template>
        <template #description>
            Track keyboard and mouse activity levels for team members using the desktop app. Only event
            counts are recorded — no key values or mouse coordinates.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 space-y-4">
                <p v-if="isBlocked()" class="text-sm text-text-secondary">
                    This workspace is blocked. Resolve billing before changing activity settings.
                </p>
                <Field orientation="horizontal">
                    <Checkbox
                        id="activityTrackingEnabled"
                        v-model:checked="form.activity_tracking_enabled" />
                    <div>
                        <FieldLabel for="activityTrackingEnabled">Enable activity level tracking</FieldLabel>
                    </div>
                </Field>
                <Field v-if="form.activity_tracking_enabled" orientation="horizontal">
                    <Checkbox
                        id="appActivitySyncEnabled"
                        v-model:checked="form.app_activity_sync_enabled" />
                    <div>
                        <FieldLabel for="appActivitySyncEnabled">Enable app activity sync</FieldLabel>
                        <p class="text-xs text-muted mt-1">
                            When enabled, application names, window titles, and sanitized URLs are sent to
                            the server for organization owners. URLs have query parameters stripped. Window
                            titles may contain sensitive information.
                        </p>
                    </div>
                </Field>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value || isBlocked()" @click="submit">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
