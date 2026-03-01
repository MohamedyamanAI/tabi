<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
    newsletter_consent: false,
});

const submit = () => {
    form.post(route('register'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};

const page = usePage<{
    terms_url: string | null;
    privacy_policy_url: string | null;
    newsletter_consent: boolean;
    jetstream: {
        hasTermsAndPrivacyPolicyFeature: boolean;
    };
    flash: {
        message: string;
    };
}>();
</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <template #actions>
            <Link
                class="py-8 text-text-secondary text-sm font-medium opacity-90 hover:opacity-100 transition"
                :href="route('login')">
                Already have an account?
                <span class="text-text-primary">Login here!</span>
            </Link>
        </template>

        <div
            v-if="page.props.flash?.message"
            class="bg-red-400 text-black text-center w-full px-3 py-1 mb-4 rounded-lg">
            {{ page.props.flash?.message }}
        </div>

        <a
            :href="route('auth.google')"
            class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-text-secondary/30 rounded-lg text-sm font-medium text-text-primary bg-transparent hover:bg-white/5 transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    fill="currentColor"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path
                    fill="currentColor"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path
                    fill="currentColor"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path
                    fill="currentColor"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Sign up with Google
        </a>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-text-secondary/20" />
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="bg-bg-primary px-2 text-text-secondary">or</span>
            </div>
        </div>

        <form @submit.prevent="submit">
            <Field>
                <FieldLabel for="name">Name</FieldLabel>
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full"
                    required
                    autofocus
                    autocomplete="name" />
                <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
            </Field>

            <Field class="mt-4">
                <FieldLabel for="email">Email</FieldLabel>
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full"
                    required
                    autocomplete="username" />
                <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
            </Field>

            <Field class="mt-4">
                <FieldLabel for="password">Password</FieldLabel>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="block w-full"
                    required
                    autocomplete="new-password" />
                <FieldError v-if="form.errors.password">{{ form.errors.password }}</FieldError>
            </Field>

            <Field class="mt-4">
                <FieldLabel for="password_confirmation">Confirm Password</FieldLabel>
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="block w-full"
                    required
                    autocomplete="new-password" />
                <FieldError v-if="form.errors.password_confirmation">{{
                    form.errors.password_confirmation
                }}</FieldError>
            </Field>

            <div
                v-if="
                    page.props.jetstream.hasTermsAndPrivacyPolicyFeature &&
                    page.props.terms_url !== null &&
                    page.props.privacy_policy_url !== null
                "
                class="mt-4">
                <Field orientation="horizontal">
                    <Checkbox id="terms" v-model:checked="form.terms" name="terms" />
                    <FieldLabel for="terms">
                        I agree to the
                        <a
                            target="_blank"
                            :href="page.props.terms_url"
                            class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >Terms of Service</a
                        >
                        and
                        <a
                            target="_blank"
                            :href="page.props.privacy_policy_url"
                            class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >Privacy Policy</a
                        >
                    </FieldLabel>
                    <FieldError v-if="form.errors.terms">{{ form.errors.terms }}</FieldError>
                </Field>
            </div>

            <div v-if="page.props.newsletter_consent" class="mt-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="newsletter_consent"
                        v-model:checked="form.newsletter_consent"
                        name="newsletter_consent" />
                    <FieldLabel for="newsletter_consent">
                        I agree to receive emails about product related updates
                    </FieldLabel>
                    <FieldError v-if="form.errors.newsletter_consent">{{
                        form.errors.newsletter_consent
                    }}</FieldError>
                </Field>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
