<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiButton from '@/components/ui/UiButton.vue';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { useToast } from '@/composables/useToast';

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

// Inertia form for server submission
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(store(), {
            onSuccess: () => {
                form.reset('password');
                showSuccess('You have been successfully logged in.');
            },
        });
    }
}
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
        page-title="Log in"
    >
        <UiForm
            :initialValues="{ email: '', password: '', remember: false }"
            @submit="onSubmit"
        >
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
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                </template>
            </UiFormField>

            <UiFormField
                name="password"
                label="Password"
                :serverError="form.errors.password"
            >
                <template #default="{ props: fieldProps, id }">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <span></span>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-sm"
                            >
                                Forgot password?
                            </TextLink>
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
                        <UiCheckbox
                            v-bind="fieldProps"
                            :id="id"
                            binary
                        />
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
                <TextLink :href="register()">Sign up</TextLink>
            </div>
        </template>
    </AuthBase>
</template>
