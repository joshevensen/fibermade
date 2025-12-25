<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/PasswordController';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { useToast } from '@/composables/useToast';

const { showSuccess } = useToast();

// Inertia form for server submission
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(PasswordController.update(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                showSuccess('Password updated successfully.');
            },
            onError: () => {
                // Reset password fields on error
                form.reset('password');
                form.reset('password_confirmation');
                form.reset('current_password');
            },
        });
    }
}
</script>

<template>
    <UiCard>
        <template #title>Update Password</template>
        <template #subtitle>
            Ensure your account is using a long, random password to stay secure
        </template>
        <template #content>
            <UiForm
                :initialValues="{ current_password: '', password: '', password_confirmation: '' }"
                @submit="onSubmit"
            >
                <UiFormField
                    name="current_password"
                    label="Current password"
                    :serverError="form.errors.current_password"
                >
                    <template #default="{ props: fieldProps, id }">
                        <UiPassword
                            v-bind="fieldProps"
                            :id="id"
                            autocomplete="current-password"
                            placeholder="Current password"
                        />
                    </template>
                </UiFormField>

                <UiFormField
                    name="password"
                    label="New password"
                    :serverError="form.errors.password"
                >
                    <template #default="{ props: fieldProps, id }">
                        <UiPassword
                            v-bind="fieldProps"
                            :id="id"
                            autocomplete="new-password"
                            placeholder="New password"
                        />
                    </template>
                </UiFormField>

                <UiFormField
                    name="password_confirmation"
                    label="Confirm password"
                    :serverError="form.errors.password_confirmation"
                >
                    <template #default="{ props: fieldProps, id }">
                        <UiPassword
                            v-bind="fieldProps"
                            :id="id"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                        />
                    </template>
                </UiFormField>

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

