<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiLink from '@/components/ui/UiLink.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const initialValues = {
    email: '',
    password: '',
    remember: false,
};

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    resetFieldsOnSuccess: ['password'],
});
</script>

<template>
    <AuthLayout
        title="Log in to your account"
        description="Enter your email and password below to log in"
        page-title="Log in"
    >
        <UiForm :initialValues="initialValues" @submit="onSubmit">
            <UiFormFieldInput
                name="email"
                label="Email address"
                :serverError="form.errors.email"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <UiFormField
                name="password"
                label="Password"
                :serverError="form.errors.password"
            >
                <template #extra>
                    <UiLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                    >
                        Forgot password?
                    </UiLink>
                </template>
                <template #default="{ props: fieldProps, id }">
                    <UiPassword
                        v-bind="fieldProps"
                        :id="id"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                </template>
            </UiFormField>

            <UiFormField name="remember">
                <template #default="{ props: fieldProps, id }">
                    <div class="flex items-center space-x-3">
                        <UiCheckbox v-bind="fieldProps" :id="id" binary />
                        <label :for="id">Remember me</label>
                    </div>
                </template>
            </UiFormField>

            <UiButton
                type="submit"
                :loading="form.processing"
                data-test="login-button"
            >
                Log in
            </UiButton>
        </UiForm>

        <template #footer>
            <div>
                Don't have an account?
                <UiLink :href="register()">Sign up</UiLink>
            </div>
        </template>
    </AuthLayout>
</template>
