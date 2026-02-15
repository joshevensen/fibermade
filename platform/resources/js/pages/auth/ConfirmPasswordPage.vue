<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';

const initialValues = {
    password: '',
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    successMessage: 'Password confirmed successfully.',
    resetFieldsOnSuccess: ['password'],
});
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
        page-title="Confirm password"
    >
        <UiForm :initialValues="initialValues" @submit="onSubmit">
            <UiFormFieldPassword
                name="password"
                label="Password"
                :serverError="form.errors.password"
                required
                autocomplete="current-password"
                autofocus
            />

            <UiButton
                type="submit"
                :loading="form.processing"
                data-test="confirm-password-button"
            >
                Confirm Password
            </UiButton>
        </UiForm>
    </AuthLayout>
</template>
