<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { Organization } from '@/types/models';
import type { Permissions } from '@/types/jetstream';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
import { isBillingActivated } from '@/utils/billing';
import { canManageBilling } from '@/utils/permissions';

const props = defineProps<{
    team: Organization & { logo_url?: string | null };
    permissions: Permissions;
}>();

const form = useForm({
    name: props.team.name,
    currency: props.team.currency,
    logo: null as File | null,
    remove_logo: false,
});

const localLogoPreview = ref<string | null>(null);
const showLogoDialog = ref(false);
const logoFileInput = ref<HTMLInputElement | null>(null);
const logoDialogSnapshot = ref<{ logo: File | null; remove_logo: boolean } | null>(null);

watch(
    () => form.logo,
    (file) => {
        if (localLogoPreview.value) {
            URL.revokeObjectURL(localLogoPreview.value);
            localLogoPreview.value = null;
        }
        if (file) {
            localLogoPreview.value = URL.createObjectURL(file);
        }
    }
);

const displayedLogoUrl = computed(() => {
    if (form.remove_logo) {
        return null;
    }
    if (localLogoPreview.value) {
        return localLogoPreview.value;
    }
    return props.team.logo_url ?? null;
});

const hasServerOrPendingLogo = computed(() => {
    return Boolean(props.team.logo_url || form.logo);
});

function openLogoDialog() {
    logoDialogSnapshot.value = {
        logo: form.logo,
        remove_logo: form.remove_logo,
    };
    showLogoDialog.value = true;
}

function cancelLogoDialog() {
    if (logoDialogSnapshot.value) {
        form.logo = logoDialogSnapshot.value.logo;
        form.remove_logo = logoDialogSnapshot.value.remove_logo;
    }
    logoDialogSnapshot.value = null;
    if (logoFileInput.value) {
        logoFileInput.value.value = '';
    }
    showLogoDialog.value = false;
}

function onLogoDialogOpenChange(open: boolean) {
    if (!open && logoDialogSnapshot.value !== null) {
        form.logo = logoDialogSnapshot.value.logo;
        form.remove_logo = logoDialogSnapshot.value.remove_logo;
        logoDialogSnapshot.value = null;
        if (logoFileInput.value) {
            logoFileInput.value.value = '';
        }
    }
    showLogoDialog.value = open;
}

function triggerLogoFilePicker() {
    logoFileInput.value?.click();
}

function submitTeamForm(onDialogClose?: () => void) {
    // POST + method spoof: PHP often does not populate $_FILES on real PUT + multipart requests.
    form
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(route('teams.update', props.team.id), {
            errorBag: 'updateTeamName',
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                if (logoFileInput.value) {
                    logoFileInput.value.value = '';
                }
                form.logo = null;
                form.remove_logo = false;
                logoDialogSnapshot.value = null;
                onDialogClose?.();
            },
        });
}

const updateTeamName = () => {
    submitTeamForm();
};

function saveLogoFromDialog() {
    submitTeamForm(() => {
        showLogoDialog.value = false;
    });
}
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title> Organization Name</template>

        <template #description> The organization's name and owner information. </template>

        <template #form>
            <!-- Organization Owner Information -->
            <div class="col-span-6 flex items-center justify-between">
                <div class="">
                    <FieldLabel>Organization Owner</FieldLabel>

                    <div class="flex items-center mt-2">
                        <img
                            class="w-12 h-12 rounded-full object-cover"
                            :src="team.owner.profile_photo_url"
                            :alt="team.owner.name" />

                        <div class="ms-4 leading-tight">
                            <div class="text-text-primary">
                                {{ team.owner.name }}
                            </div>
                            <div class="text-text-secondary text-sm">
                                {{ team.owner.email }}
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                        <PrimaryButton :icon="CreditCardIcon" type="button">
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>

            <!-- Organization Name -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="name">Organization Name</FieldLabel>

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full"
                    :disabled="!permissions.canUpdateTeam" />

                <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
            </Field>

            <!-- Currency -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="currency">Currency</FieldLabel>
                <select
                    id="currency"
                    v-model="form.currency"
                    name="currency"
                    :disabled="!permissions.canUpdateTeam"
                    class="block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a currency</option>
                    <option
                        v-for="(currencyTranslated, currencyKey) in $page.props.currencies"
                        :key="currencyKey"
                        :value="currencyKey">
                        {{ currencyKey }} - {{ currencyTranslated }}
                    </option>
                </select>
                <FieldError v-if="form.errors.currency">{{ form.errors.currency }}</FieldError>
            </Field>

            <Field v-if="!permissions.canUpdateTeam" class="col-span-6 sm:col-span-4 text-left">
                <FieldLabel>Organization logo</FieldLabel>
                <div class="mt-2 flex items-center justify-start gap-3">
                    <img
                        v-if="props.team.logo_url"
                        class="h-12 w-12 rounded-full object-cover"
                        :src="props.team.logo_url"
                        :alt="props.team.name" />
                    <div
                        v-else
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-900 text-sm font-medium text-white">
                        {{ props.team.name.slice(0, 1).toUpperCase() }}
                    </div>
                </div>
            </Field>

            <Field v-if="permissions.canUpdateTeam" class="col-span-6 sm:col-span-4 text-left">
                <FieldLabel>Organization logo</FieldLabel>
                <div class="mt-1 flex w-full flex-col items-start gap-2">
                <p class="text-sm text-text-secondary">
                    Click the image to upload, change, or remove the logo.
                </p>
                <button
                    type="button"
                    class="group relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border-0 bg-transparent p-0 transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :title="'Change organization logo'"
                    @click="openLogoDialog">
                    <img
                        v-if="displayedLogoUrl"
                        class="h-12 w-12 rounded-full object-cover"
                        :src="displayedLogoUrl"
                        :alt="form.name" />
                    <div
                        v-else
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-900 text-sm font-medium text-white">
                        {{ form.name.slice(0, 1).toUpperCase() || '?' }}
                    </div>
                </button>
                <FieldError v-if="form.errors.logo">{{ form.errors.logo }}</FieldError>
                </div>
            </Field>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>

    <Dialog
        v-if="permissions.canUpdateTeam"
        :open="showLogoDialog"
        @update:open="onLogoDialogOpenChange">
        <DialogContent class="max-w-md gap-0 overflow-hidden p-0">
            <div class="flex flex-col gap-5 px-6 pb-2 pt-6 sm:px-7 sm:pb-3 sm:pt-7">
                <DialogHeader class="space-y-2 text-left">
                    <DialogTitle>Organization logo</DialogTitle>
                    <DialogDescription>
                        Upload a JPEG, PNG, or WebP image. If you remove the logo, the first letter of the organization
                        name is shown instead.
                    </DialogDescription>
                </DialogHeader>

            <div class="flex flex-col gap-4">
                <div class="flex justify-start">
                    <div
                        class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-transparent">
                        <img
                            v-if="displayedLogoUrl"
                            class="h-full w-full rounded-full object-cover"
                            :src="displayedLogoUrl"
                            :alt="form.name" />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center rounded-full bg-blue-900 text-2xl font-medium text-white">
                            {{ form.name.slice(0, 1).toUpperCase() || '?' }}
                        </div>
                    </div>
                </div>

                <input
                    ref="logoFileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="sr-only"
                    :disabled="form.remove_logo"
                    @change="
                        (e) => {
                            const t = e.target as HTMLInputElement;
                            form.logo = t.files?.[0] ?? null;
                            if (form.logo) {
                                form.remove_logo = false;
                            }
                        }
                    " />

                <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-start">
                    <SecondaryButton type="button" :disabled="form.remove_logo" @click="triggerLogoFilePicker">
                        Choose image
                    </SecondaryButton>
                </div>

                <label
                    v-if="hasServerOrPendingLogo"
                    class="flex cursor-pointer items-center gap-2 text-sm text-text-secondary">
                    <input v-model="form.remove_logo" type="checkbox" class="rounded border-input-border" />
                    Remove logo
                </label>

                <FieldError v-if="form.errors.logo">{{ form.errors.logo }}</FieldError>
            </div>
            </div>

            <DialogFooter class="gap-2 border-t border-card-background-separator px-6 py-4 sm:px-7">
                <SecondaryButton type="button" :disabled="form.processing" @click="cancelLogoDialog">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="saveLogoFromDialog">
                    Save logo
                </PrimaryButton>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
