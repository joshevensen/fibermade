<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { update as updateAccount } from '@/routes/account';

interface Account {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    state_region?: string | null;
    postal_code?: string | null;
}

interface Props {
    account: Account;
}

const props = defineProps<Props>();

const initialValues = {
    name: props.account.name || '',
    email: props.account.email || null,
    phone: props.account.phone || null,
    address_line1: props.account.address_line1 || null,
    address_line2: props.account.address_line2 || null,
    city: props.account.city || null,
    state_region: props.account.state_region || null,
    postal_code: props.account.postal_code || null,
};

const { form, onSubmit } = useFormSubmission({
    route: updateAccount,
    initialValues,
    successMessage: 'Account updated successfully.',
});
</script>

<template>
    <UiCard>
        <template #title>Account Information</template>
        <template #subtitle>Update your account details</template>
        <template #content>
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="email"
                    label="Email"
                    type="email"
                    :server-error="form.errors.email"
                />

                <UiFormFieldInput
                    name="phone"
                    label="Phone"
                    type="tel"
                    :server-error="form.errors.phone"
                />

                <UiFormFieldAddress :show-line2="true" :errors="form.errors" />

                <UiButton
                    type="submit"
                    :loading="form.processing"
                    data-test="update-account-button"
                >
                    Save
                </UiButton>
            </UiForm>
        </template>
    </UiCard>
</template>
