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
import { isAllowedToUseScreenshots, isBillingActivated } from '@/utils/billing';
import { canManageBilling, canUpdateOrganization } from '@/utils/permissions';
import { Link } from '@inertiajs/vue3';
import { CreditCardIcon, CameraIcon } from '@heroicons/vue/20/solid';

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
            <div v-if="!isAllowedToUseScreenshots()" class="col-span-6 sm:col-span-4">
                <div
                    class="rounded-full flex items-center justify-center w-20 h-20 mx-auto border border-border-tertiary bg-secondary">
                    <CameraIcon class="w-12" />
                </div>
                <div class="max-w-sm text-center mx-auto py-4 text-base">
                    <p class="py-1">
                        Screenshot capture is available on the <strong>Pro plan</strong>.
                    </p>
                    <p class="py-1">
                        To enable screenshot settings for your organization,
                        <strong>please upgrade your billing plan</strong>.
                    </p>

                    <Link
                        v-if="isBillingActivated() && canManageBilling() && canUpdateOrganization()"
                        href="/billing">
                        <PrimaryButton type="button" class="mt-6 px-4 py-2 text-sm">
                            <span class="inline-flex items-center gap-2">
                                <CreditCardIcon class="w-4 h-4" />
                                <span>Manage billing</span>
                            </span>
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
            <div v-else class="col-span-6 sm:col-span-4 space-y-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="screenshotsEnabled"
                        v-model:checked="form.screenshots_enabled"
                        :disabled="!isAllowedToUseScreenshots()" />
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
                        min="3"
                        max="60"
                        :disabled="!isAllowedToUseScreenshots()"
                        class="w-24" />
                </Field>
                <Field v-if="form.screenshots_enabled" orientation="horizontal">
                    <Checkbox
                        id="screenshotsBlurred"
                        v-model:checked="form.screenshots_blurred"
                        :disabled="!isAllowedToUseScreenshots()" />
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
            <PrimaryButton
                :disabled="mutation.isPending.value || !isAllowedToUseScreenshots()"
                @click="submit">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
