<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiButton from '@/components/ui/UiButton.vue';
import TextLink from '@/components/TextLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { useToast } from '@/composables/useToast';

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

// Inertia form for server submission
const form = useForm({
    email: '',
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(email(), {
            onSuccess: () => {
                showSuccess('We have emailed your password reset link!');
            },
        });
    }
}
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description="Enter your email to receive a password reset link"
        page-title="Forgot password"
    >
        <UiForm :initialValues="{ email: '' }" @submit="onSubmit">
            <UiFormField
                name="email"
                label="Email address"
                :serverError="form.errors.email"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiInputText
                        v-bind="fieldProps"
                        :id="id"
                        type="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                    />
                </template>
            </UiFormField>

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
            <TextLink :href="login()">log in</TextLink>
        </template>
    </AuthLayout>
</template>
