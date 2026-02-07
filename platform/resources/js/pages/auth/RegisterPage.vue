<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
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
        business_name: '',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
        privacy_accepted: false,
        marketing_opt_in: false,
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
                business_name: '',
                password: '',
                password_confirmation: '',
                terms_accepted: false,
                privacy_accepted: false,
                marketing_opt_in: false,
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

            <UiFormFieldInput
                name="business_name"
                label="Business name"
                :serverError="form.errors.business_name"
                type="text"
                required
                autocomplete="organization"
                placeholder="Your business name"
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

            <div class="space-y-3">
                <UiFormFieldCheckbox
                    name="terms_accepted"
                    :serverError="form.errors.terms_accepted"
                    required
                    label="I agree to the Terms of Service"
                />

                <UiFormFieldCheckbox
                    name="privacy_accepted"
                    :serverError="form.errors.privacy_accepted"
                    required
                    label="I agree to the Privacy Policy"
                />

                <UiFormFieldCheckbox
                    name="marketing_opt_in"
                    :serverError="form.errors.marketing_opt_in"
                    label="I'd like to receive product updates and tips"
                />
            </div>

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
