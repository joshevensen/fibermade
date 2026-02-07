<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useToast } from '@/composables/useToast';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { watch } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const { showSuccess } = useToast();

// Show status message as toast when it changes
watch(
    () => props.status,
    (status) => {
        if (status) showSuccess(status);
    },
    { immediate: true },
);

const { form, onSubmit } = useFormSubmission({
    route: email,
    initialValues: {
        email: '',
    },
    successMessage: 'We have emailed your password reset link!',
});
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description="Enter your email to receive a password reset link"
        page-title="Forgot password"
    >
        <UiForm :initialValues="{ email: '' }" @submit="onSubmit">
            <UiFormFieldInput
                name="email"
                label="Email address"
                :serverError="form.errors.email"
                type="email"
                autocomplete="off"
                autofocus
                placeholder="email@example.com"
            />

            <UiButton
                type="submit"
                :loading="form.processing"
                data-test="email-password-reset-link-button"
            >
                Email password reset link
            </UiButton>
        </UiForm>

        <template #footer>
            <span>Or, return to</span>
            <UiLink :href="login()">log in</UiLink>
        </template>
    </AuthLayout>
</template>
