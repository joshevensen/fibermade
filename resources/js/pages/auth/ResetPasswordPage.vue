<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiButton from '@/components/ui/UiButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';
import { useToast } from '@/composables/useToast';

const props = defineProps<{
    token: string;
    email: string;
}>();

const { showSuccess } = useToast();
const inputEmail = ref(props.email);

// Inertia form for server submission
const form = useForm({
    email: props.email,
    password: '',
    password_confirmation: '',
    token: props.token,
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        // Apply transform: merge token and email
        const transformedData = {
            ...values,
            token: props.token,
            email: props.email,
        };
        Object.assign(form, transformedData);
        form.submit(update(), {
            onSuccess: () => {
                form.reset('password');
                form.reset('password_confirmation');
                showSuccess('Your password has been reset successfully.');
            },
        });
    }
}
</script>

<template>
    <AuthLayout
        title="Reset password"
        description="Please enter your new password below"
        page-title="Reset password"
    >

        <UiForm
            :initialValues="{ email: props.email, password: '', password_confirmation: '' }"
            @submit="onSubmit"
        >
            <UiFormField
                name="email"
                label="Email"
                :serverError="form.errors.email"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiInputText
                        v-bind="fieldProps"
                        :id="id"
                        type="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        readonly
                    />
                </template>
            </UiFormField>

            <UiFormField
                name="password"
                label="Password"
                :serverError="form.errors.password"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiPassword
                        v-bind="fieldProps"
                        :id="id"
                        autocomplete="new-password"
                        autofocus
                        placeholder="Password"
                    />
                </template>
            </UiFormField>

            <UiFormField
                name="password_confirmation"
                label="Confirm Password"
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
                data-test="reset-password-button"
            >
                Reset password
            </UiButton>
        </UiForm>
    </AuthLayout>
</template>
