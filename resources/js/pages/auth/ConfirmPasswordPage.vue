<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiButton from '@/components/ui/UiButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { useToast } from '@/composables/useToast';

const { showSuccess } = useToast();

// Inertia form for server submission
const form = useForm({
    password: '',
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(store(), {
            onSuccess: () => {
                form.reset();
                showSuccess('Password confirmed successfully.');
            },
        });
    }
}
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
        page-title="Confirm password"
    >
        <UiForm :initialValues="{ password: '' }" @submit="onSubmit">
            <UiFormField
                name="password"
                label="Password"
                :serverError="form.errors.password"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiPassword
                        v-bind="fieldProps"
                        :id="id"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                </template>
            </UiFormField>

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
