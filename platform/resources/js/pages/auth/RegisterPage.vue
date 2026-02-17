<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { IconList } from '@/composables/useIcon';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { useForm } from '@inertiajs/vue3';

const features = [
    'Wholesale catalog with fiber-specific terminology',
    'Store relationship management with per-store terms',
    'Inline ordering for your wholesale customers',
    'Smart inventory reservation (wholesale vs. retail)',
    'Bi-directional Shopify sync',
    '30-day money-back guarantee',
];

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

    const csrfToken = (
        document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
    )?.content;

    const response = await fetch(url.toString(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
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
            flat[key] = Array.isArray(value)
                ? (value[0] as string)
                : (value as string);
        }
        form.setError(flat);
        return;
    }

    if (data.message) {
        form.setError({ form: data.message } as Record<string, string>);
    }
}
</script>

<template>
    <AuthLayout title="Create an account" page-title="Register" wide>
        <UiForm :initialValues="initialValues" @submit="onSubmit">
            <p
                v-if="(form.errors as Record<string, string>)['form']"
                class="text-destructive text-sm"
                role="alert"
            >
                {{ (form.errors as Record<string, string>)['form'] }}
            </p>

            <div class="grid gap-8 lg:grid-cols-5">
                <!-- Left column: Form fields -->
                <div class="space-y-4 lg:col-span-3">   
                    <UiFormFieldInput
                        name="business_name"
                        label="Business name"
                        :serverError="form.errors.business_name"
                        type="text"
                        required
                        autofocus
                        autocomplete="organization"
                    />

                    <UiFormFieldInput
                        name="name"
                        label="Your Name"
                        :serverError="form.errors.name"
                        type="text"
                        required
                        autocomplete="name"
                    />

                    <UiFormFieldInput
                        name="email"
                        label="Email address"
                        :serverError="form.errors.email"
                        type="email"
                        required
                        autocomplete="email"
                    />

                    <UiFormFieldPassword
                        name="password"
                        label="Password"
                        :serverError="form.errors.password"
                        required
                        autocomplete="new-password"
                    />

                    <UiFormFieldPassword
                        name="password_confirmation"
                        label="Confirm password"
                        :serverError="form.errors.password_confirmation"
                        required
                        autocomplete="new-password"
                    />

                    <UiButton
                        type="submit"
                        :loading="form.processing"
                        data-test="register-user-button"
                        fullWidth
                    >
                        Continue to payment
                    </UiButton>
                </div>

                <!-- Right column: Pricing + checkboxes -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <!-- Pricing card -->
                    <div
                        class="rounded-2xl bg-surface-200 py-8 text-center inset-ring inset-ring-surface-900/5 flex flex-col justify-center"
                    >
                        <div class="mx-auto max-w-xs px-4">
                            <p
                                class="text-lg font-semibold text-surface-600"
                            >
                                One Price, No Hidden Fees
                            </p>
                            <p
                                class="mt-4 flex items-baseline justify-center gap-x-2"
                            >
                                <span
                                    class="text-5xl font-semibold tracking-tight text-surface-900"
                                >
                                    $39
                                </span>
                                <span
                                    class="text-base/6 font-semibold tracking-wide text-surface-600"
                                >
                                    /month
                                </span>
                            </p>
                            <p
                                class="mt-3 text-center text-xs text-surface-500"
                            >
                                Cancel within 30 days for a full refund.
                            </p>
                        </div>
                    </div>

                    <!-- Terms & Privacy checkboxes -->
                    <div class="space-y-3 border-t border-surface-200 pt-4">
                        <UiFormFieldCheckbox
                            name="terms_accepted"
                            :serverError="form.errors.terms_accepted"
                            required
                        >
                            <template #label>
                                I agree to the
                                <a
                                    href="/terms"
                                    target="_blank"
                                    class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                                    >Terms of Service</a
                                >&nbsp;
                            </template>
                        </UiFormFieldCheckbox>

                        <UiFormFieldCheckbox
                            name="privacy_accepted"
                            :serverError="form.errors.privacy_accepted"
                            required
                        >
                            <template #label>
                                I agree to the
                                <a
                                    href="/privacy"
                                    target="_blank"
                                    class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                                    >Privacy Policy</a
                                >&nbsp;
                            </template>
                        </UiFormFieldCheckbox>
                    </div>
                </div>
            </div>
        </UiForm>

        <template #footer>
            Already have an account?
            <UiLink :href="login()" class="underline underline-offset-4"
                >Log in</UiLink
            >
        </template>
    </AuthLayout>
</template>
