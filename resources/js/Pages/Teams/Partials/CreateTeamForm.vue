<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { User } from '@/types/models';
import { initializeStores } from '@/utils/init';

const form = useForm({
    name: '',
    logo: null as File | null,
});

const createTeam = () => {
    form.post(route('teams.store'), {
        errorBag: 'createTeam',
        preserveScroll: true,
        forceFormData: form.logo !== null,
        onSuccess: () => {
            initializeStores();
        },
    });
};
const page = usePage<{
    auth: {
        user: User;
    };
}>();
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title> Organization Details</template>

        <template #description>
            Create a new organization to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <FieldLabel>Organization Owner</FieldLabel>

                <div class="flex items-center mt-2">
                    <img
                        class="object-cover w-12 h-12 rounded-full"
                        :src="page.props.auth.user.profile_photo_url"
                        :alt="page.props.auth.user.name" />

                    <div class="ms-4 leading-tight">
                        <div class="text-text-primary">
                            {{ page.props.auth.user.name }}
                        </div>
                        <div class="text-sm text-text-secondary">
                            {{ page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="name">Organization Name</FieldLabel>
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full"
                    autofocus />
                <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
            </Field>

            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="logo">Organization logo (optional)</FieldLabel>
                <input
                    id="logo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-text-secondary file:mr-4 file:rounded-md file:border-0 file:bg-card-background file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-primary"
                    @change="
                        (e) => {
                            const t = e.target as HTMLInputElement;
                            form.logo = t.files?.[0] ?? null;
                        }
                    " />
                <FieldError v-if="form.errors.logo">{{ form.errors.logo }}</FieldError>
            </Field>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Create
            </PrimaryButton>
        </template>
    </FormSection>
</template>
