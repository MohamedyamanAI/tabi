<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { Checkbox } from '@/packages/ui/src';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import { useMutation, useQueryClient } from '@tanstack/vue-query';

const store = useOrganizationStore();
const { updateOrganization } = store;
const { organization } = storeToRefs(store);
const queryClient = useQueryClient();

const form = ref<{
    idle_detection_enabled: boolean;
    idle_threshold_minutes: number;
}>({
    idle_detection_enabled: true,
    idle_threshold_minutes: 5,
});

onMounted(async () => {
    form.value.idle_detection_enabled = organization.value?.idle_detection_enabled ?? true;
    form.value.idle_threshold_minutes = organization.value?.idle_threshold_minutes ?? 5;
});

const mutation = useMutation({
    mutationFn: (values: Partial<UpdateOrganizationBody>) => updateOrganization(values),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
});

async function submit() {
    await mutation.mutateAsync({
        idle_detection_enabled: form.value.idle_detection_enabled,
        idle_threshold_minutes: form.value.idle_threshold_minutes,
    });
}
</script>

<template>
    <FormSection>
        <template #title>Idle Detection Settings</template>
        <template #description>
            Configure idle detection for your organization. When enabled, the desktop app will
            detect user inactivity and prompt to handle idle time during active time tracking.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 space-y-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="idleDetectionEnabled"
                        v-model:checked="form.idle_detection_enabled" />
                    <FieldLabel for="idleDetectionEnabled">Enable Idle Detection</FieldLabel>
                </Field>
                <Field v-if="form.idle_detection_enabled">
                    <FieldLabel for="idleThresholdMinutes"
                        >Idle threshold (minutes)</FieldLabel
                    >
                    <TextInput
                        id="idleThresholdMinutes"
                        v-model="form.idle_threshold_minutes"
                        type="number"
                        min="1"
                        max="60"
                        class="w-24" />
                </Field>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value" @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
