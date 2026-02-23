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
    screenshots_enabled: boolean;
    screenshot_interval_minutes: number;
    screenshots_blurred: boolean;
}>({
    screenshots_enabled: false,
    screenshot_interval_minutes: 5,
    screenshots_blurred: true,
});

onMounted(async () => {
    form.value.screenshots_enabled = organization.value?.screenshots_enabled ?? false;
    form.value.screenshot_interval_minutes = organization.value?.screenshot_interval_minutes ?? 5;
    form.value.screenshots_blurred = organization.value?.screenshots_blurred ?? true;
});

const mutation = useMutation({
    mutationFn: (values: Partial<UpdateOrganizationBody>) => updateOrganization(values),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
});

async function submit() {
    await mutation.mutateAsync({
        screenshots_enabled: form.value.screenshots_enabled,
        screenshot_interval_minutes: form.value.screenshot_interval_minutes,
        screenshots_blurred: form.value.screenshots_blurred,
    });
}
</script>

<template>
    <FormSection>
        <template #title>Screenshot Settings</template>
        <template #description>
            Configure screenshot capture for your organization. When enabled, the desktop app will
            periodically capture screenshots during active time tracking.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 space-y-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="screenshotsEnabled"
                        v-model:checked="form.screenshots_enabled" />
                    <FieldLabel for="screenshotsEnabled">Enable Screenshots</FieldLabel>
                </Field>
                <Field v-if="form.screenshots_enabled">
                    <FieldLabel for="screenshotIntervalMinutes"
                        >Capture interval (minutes)</FieldLabel
                    >
                    <TextInput
                        id="screenshotIntervalMinutes"
                        v-model="form.screenshot_interval_minutes"
                        type="number"
                        min="1"
                        max="60"
                        class="w-24" />
                </Field>
                <Field v-if="form.screenshots_enabled" orientation="horizontal">
                    <Checkbox
                        id="screenshotsBlurred"
                        v-model:checked="form.screenshots_blurred" />
                    <div>
                        <FieldLabel for="screenshotsBlurred">Blur screenshots</FieldLabel>
                        <p class="text-xs text-muted">
                            When enabled, screenshots are pixelated to protect
                            privacy while still showing general activity. When
                            disabled, screenshots are captured at full clarity.
                        </p>
                    </div>
                </Field>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value" @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
