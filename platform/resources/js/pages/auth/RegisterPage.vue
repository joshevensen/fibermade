<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import UiLink from '@/components/ui/UiLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { login } from '@/routes';

const initialValues = {
    name: '',
    email: '',
    business_name: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
    privacy_accepted: false,
    marketing_opt_in: false,
};

const form = useForm(initialValues);

function getPromoFromUrl(): string | null {
    if (typeof window === 'undefined') return null;
    return new URL(window.location.href).searchParams.get('promo');
}

async function onSubmit({
    valid,
    values,
}: {
    valid: boolean;
    values: Record<string, unknown>;
}): Promise<void> {
    if (!valid) return;

    const promo = getPromoFromUrl();
    const url = new URL('/register/checkout', window.location.origin);
    if (promo) url.searchParams.set('promo', promo);

    form.clearErrors();
    form.processing = true;

    const body = {
        name: values.name,
        email: values.email,
        business_name: values.business_name,
        password: values.password,
        password_confirmation: values.password_confirmation,
        terms_accepted: values.terms_accepted,
        privacy_accepted: values.privacy_accepted,
        marketing_opt_in: values.marketing_opt_in ?? false,
    };

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

    const response = await fetch(url.toString(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify(body),
    });

    form.processing = false;

    const data = await response.json().catch(() => ({}));

    if (response.ok && data.redirect_url) {
        window.location.href = data.redirect_url;
        return;
    }

    if (response.status === 422 && data.errors) {
        const flat: Record<string, string> = {};
        for (const [key, value] of Object.entries(data.errors)) {
            flat[key] = Array.isArray(value) ? (value[0] as string) : (value as string);
        }
        form.setError(flat);
        return;
    }

    if (data.message) {
        form.setError({ form: data.message });
    }
}
</script>

<template>
    <AuthLayout
        title="Create an account"
        description="Enter your details below to create your account"
        page-title="Register"
    >
        <UiForm :initialValues="initialValues" @submit="onSubmit">
            <p
                v-if="form.errors.form"
                class="text-destructive text-sm"
                role="alert"
            >
                {{ form.errors.form }}
            </p>
            <UiFormFieldInput
                name="name"
                label="Name"
                :serverError="form.errors.name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Full name"
            />

            <UiFormFieldInput
                name="email"
                label="Email address"
                :serverError="form.errors.email"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <UiFormFieldInput
                name="business_name"
                label="Business name"
                :serverError="form.errors.business_name"
                type="text"
                required
                autocomplete="organization"
                placeholder="Your business name"
            />

            <UiFormFieldPassword
                name="password"
                label="Password"
                :serverError="form.errors.password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />

            <UiFormFieldPassword
                name="password_confirmation"
                label="Confirm password"
                :serverError="form.errors.password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />

            <div class="space-y-3">
                <UiFormFieldCheckbox
                    name="terms_accepted"
                    :serverError="form.errors.terms_accepted"
                    required
                    label="I agree to the Terms of Service"
                />

                <UiFormFieldCheckbox
                    name="privacy_accepted"
                    :serverError="form.errors.privacy_accepted"
                    required
                    label="I agree to the Privacy Policy"
                />

                <UiFormFieldCheckbox
                    name="marketing_opt_in"
                    :serverError="form.errors.marketing_opt_in"
                    label="I'd like to receive product updates and tips"
                />
            </div>

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
            <UiLink :href="login()" class="underline underline-offset-4"
                >Log in</UiLink
            >
        </template>
    </AuthLayout>
</template>
