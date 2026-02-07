<script setup lang="ts">
import { acceptStore } from '@/actions/App/Http/Controllers/InviteController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldPassword from '@/components/ui/UiFormFieldPassword.vue';
import UiLink from '@/components/ui/UiLink.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Invite {
    store_name: string;
    owner_name: string;
    email: string;
}

const props = defineProps<{
    token: string;
    invite: Invite;
    creator_name: string;
}>();

const initialValues = {
    store_name: props.invite.store_name ?? '',
    owner_name: props.invite.owner_name ?? '',
    email: props.invite.email ?? '',
    address_line1: '',
    city: '',
    state_region: '',
    postal_code: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
    privacy_accepted: false,
};

const page = usePage();
const redirectErrors = computed(
    () =>
        (page.props as { errors?: Record<string, string | string[]> }).errors ??
        {},
);

const { form, onSubmit } = useFormSubmission({
    route: () => acceptStore.post(props.token),
    initialValues,
    resetFieldsOnSuccess: ['password', 'password_confirmation'],
});

function normalizeError(v: string | string[] | undefined): string | undefined {
    if (v == null) return undefined;
    return Array.isArray(v) ? v[0] : v;
}

const normalizedErrors = computed(() => {
    const raw = {
        ...redirectErrors.value,
        ...form.errors,
    } as Record<string, string | string[] | undefined>;
    const out: Record<string, string> = {};
    for (const [k, v] of Object.entries(raw)) {
        const s = normalizeError(v);
        if (s) out[k] = s;
    }
    return out;
});

const inviteError = computed(() =>
    normalizeError(normalizedErrors.value.invite),
);
</script>

<template>
    <AuthLayout
        :title="`${creator_name} invited you to connect as a store`"
        description="Confirm or correct the details below, add your address, and create your account."
        page-title="Accept invite"
    >
        <UiMessage v-if="inviteError" severity="error" class="mb-6">
            {{ inviteError }}
        </UiMessage>

        <UiForm :initialValues="initialValues" @submit="onSubmit">
            <p class="text-muted-foreground mb-4 text-sm">
                Confirm or correct the details your vendor provided.
            </p>

            <UiFormFieldInput
                name="store_name"
                label="Store name"
                :serverError="normalizedErrors.store_name"
                type="text"
                required
                autofocus
                autocomplete="organization"
                placeholder="Store name"
            />

            <UiFormFieldInput
                name="owner_name"
                label="Owner name"
                :serverError="normalizedErrors.owner_name"
                type="text"
                autocomplete="name"
                placeholder="Your name"
            />

            <UiFormFieldInput
                name="email"
                label="Email address"
                :serverError="normalizedErrors.email"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <UiFormFieldAddress :errors="normalizedErrors" />

            <UiFormFieldPassword
                name="password"
                label="Password"
                :serverError="normalizedErrors.password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />

            <UiFormFieldPassword
                name="password_confirmation"
                label="Confirm password"
                :serverError="normalizedErrors.password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />

            <div class="space-y-3">
                <UiFormField
                    name="terms_accepted"
                    :serverError="normalizedErrors.terms_accepted"
                    required
                >
                    <template #default="{ props: fieldProps, id }">
                        <div class="flex items-center gap-3">
                            <UiCheckbox v-bind="fieldProps" :id="id" binary />
                            <label :for="id" class="cursor-pointer text-sm">
                                I agree to the Terms of Service
                                <span class="text-surface-500">(required)</span>
                            </label>
                        </div>
                    </template>
                </UiFormField>

                <UiFormField
                    name="privacy_accepted"
                    :serverError="normalizedErrors.privacy_accepted"
                    required
                >
                    <template #default="{ props: fieldProps, id }">
                        <div class="flex items-center gap-3">
                            <UiCheckbox v-bind="fieldProps" :id="id" binary />
                            <label :for="id" class="cursor-pointer text-sm">
                                I agree to the Privacy Policy
                                <span class="text-surface-500">(required)</span>
                            </label>
                        </div>
                    </template>
                </UiFormField>
            </div>

            <UiButton type="submit" :loading="form.processing">
                Create account
            </UiButton>
        </UiForm>

        <template #footer>
            Already have an account?
            <UiLink :href="login()" class="underline underline-offset-4">
                Log in
            </UiLink>
        </template>
    </AuthLayout>
</template>
