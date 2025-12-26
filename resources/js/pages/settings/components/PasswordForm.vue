<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';

const { form, onSubmit } = useFormSubmission({
    route: UserController.updatePassword,
    initialValues: {
        current_password: '',
        password: '',
        password_confirmation: '',
    },
    successMessage: 'Password updated successfully.',
    preserveScroll: true,
    resetFieldsOnError: [
        'password',
        'password_confirmation',
        'current_password',
    ],
});
</script>

<template>
    <UiCard>
        <template #title>Update Password</template>
        <template #subtitle>
            Ensure your account is using a long, random password to stay secure
        </template>
        <template #content>
            <UiForm
                :initialValues="{
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                }"
                @submit="onSubmit"
            >
                <UiFormFieldPassword
                    name="current_password"
                    label="Current password"
                    :serverError="form.errors.current_password"
                    autocomplete="current-password"
                    placeholder="Current password"
                />

                <UiFormFieldPassword
                    name="password"
                    label="New password"
                    :serverError="form.errors.password"
                    autocomplete="new-password"
                    placeholder="New password"
                    feedback
                />

                <UiFormFieldPassword
                    name="password_confirmation"
                    label="Confirm password"
                    :serverError="form.errors.password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />

                <UiButton
                    type="submit"
                    :loading="form.processing"
                    data-test="update-password-button"
                >
                    Save password
                </UiButton>
            </UiForm>
        </template>
    </UiCard>
</template>
