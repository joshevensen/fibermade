<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    },
    successMessage: 'Your account has been created successfully.',
    resetFieldsOnSuccess: ['password', 'password_confirmation'],
});
</script>

<template>
    <AuthLayout
        title="Create an account"
        description="Enter your details below to create your account"
        page-title="Register"
    >
        <UiForm
            :initialValues="{
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            }"
            @submit="onSubmit"
        >
            <UiFormFieldInput
                name="name"
                label="Name"
                :serverError="form.errors.name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Full name"
            />

            <UiFormFieldInput
                name="email"
                label="Email address"
                :serverError="form.errors.email"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <UiFormFieldPassword
                name="password"
                label="Password"
                :serverError="form.errors.password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />

            <UiFormFieldPassword
                name="password_confirmation"
                label="Confirm password"
                :serverError="form.errors.password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />

            <UiButton
                type="submit"
                :loading="form.processing"
                data-test="register-user-button"
            >
                Create account
            </UiButton>
        </UiForm>

        <template #footer>
            Already have an account?
            <UiLink :href="login()" class="underline underline-offset-4"
                >Log in</UiLink
            >
        </template>
    </AuthLayout>
</template>
