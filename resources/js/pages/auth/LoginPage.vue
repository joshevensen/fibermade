<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiLink from '@/components/ui/UiLink.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useToast } from '@/composables/useToast';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { watch } from 'vue';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
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
    route: store,
    initialValues: {
        email: '',
        password: '',
        remember: false,
    },
    successMessage: 'You have been successfully logged in.',
    resetFieldsOnSuccess: ['password'],
});
</script>

<template>
    <AuthLayout
        title="Log in to your account"
        description="Enter your email and password below to log in"
        page-title="Log in"
    >
        <UiForm
            :initialValues="{ email: '', password: '', remember: false }"
            @submit="onSubmit"
        >
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
                <template #default="{ props: fieldProps, id }">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <span></span>
                            <UiLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-sm"
                            >
                                Forgot password?
                            </UiLink>
                        </div>
                        <UiPassword
                            v-bind="fieldProps"
                            :id="id"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />
                    </div>
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
