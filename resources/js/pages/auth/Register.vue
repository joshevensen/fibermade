<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiButton from '@/components/ui/UiButton.vue';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { useToast } from '@/composables/useToast';

const { showSuccess } = useToast();

// Inertia form for server submission
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(store(), {
            onSuccess: () => {
                form.reset('password');
                form.reset('password_confirmation');
                showSuccess('Your account has been created successfully.');
            },
        });
    }
}
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
        page-title="Register"
    >

        <UiForm
            :initialValues="{ name: '', email: '', password: '', password_confirmation: '' }"
            @submit="onSubmit"
        >
            <UiFormField
                name="name"
                label="Name"
                :serverError="form.errors.name"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiInputText
                        v-bind="fieldProps"
                        :id="id"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Full name"
                    />
                </template>
            </UiFormField>

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
                    <UiPassword
                        v-bind="fieldProps"
                        :id="id"
                        required
                        autocomplete="new-password"
                        placeholder="Password"
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
                        required
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                </template>
            </UiFormField>

            <UiButton
                type="submit"
                :loading="form.processing"
                data-test="register-user-button"
            >
                Create account
            </UiButton>
        </UiForm>

        <template #footer>
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                >Log in</TextLink
            >
        </template>
    </AuthBase>
</template>
