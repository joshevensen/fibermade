<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();

const { form, onSubmit } = useFormSubmission({
    route: update,
    initialValues: {
        email: props.email,
        password: '',
        password_confirmation: '',
        token: props.token,
    },
    successMessage: 'Your password has been reset successfully.',
    resetFieldsOnSuccess: ['password', 'password_confirmation'],
    transform: (values) => ({
        ...values,
        token: props.token,
        email: props.email,
    }),
});
</script>

<template>
    <AuthLayout
        title="Reset password"
        description="Please enter your new password below"
        page-title="Reset password"
    >
        <UiForm
            :initialValues="{
                email: props.email,
                password: '',
                password_confirmation: '',
            }"
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
                        :value="props.email"
                        readonly
                    />
                </template>
            </UiFormField>

            <UiFormFieldPassword
                name="password"
                label="Password"
                :serverError="form.errors.password"
                autocomplete="new-password"
                autofocus
                placeholder="Password"
            />

            <UiFormFieldPassword
                name="password_confirmation"
                label="Confirm Password"
                :serverError="form.errors.password_confirmation"
                autocomplete="new-password"
                placeholder="Confirm password"
            />

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
