<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';

interface Props {
    user: {
        name: string;
        email: string;
        marketing_opt_in?: boolean;
    };
}

const props = defineProps<Props>();

const { form, onSubmit } = useFormSubmission({
    route: UserController.update,
    initialValues: {
        name: props.user.name,
        email: props.user.email,
        marketing_opt_in: props.user.marketing_opt_in ?? false,
    },
    successMessage: 'Profile updated successfully.',
});
</script>

<template>
    <UiCard>
        <template #title>Profile Information</template>
        <template #subtitle>Update your name and email address</template>
        <template #content>
            <UiForm
                :initialValues="{
                    name: user.name,
                    email: user.email,
                    marketing_opt_in: user.marketing_opt_in ?? false,
                }"
                @submit="onSubmit"
            >
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    :serverError="form.errors.name"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />

                <UiFormFieldInput
                    name="email"
                    label="Email address"
                    :serverError="form.errors.email"
                    type="email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />

                <UiFormFieldCheckbox
                    name="marketing_opt_in"
                    :serverError="form.errors.marketing_opt_in"
                    label="I'd like to receive product updates and tips"
                />

                <UiButton
                    type="submit"
                    :loading="form.processing"
                    data-test="update-profile-button"
                >
                    Save
                </UiButton>
            </UiForm>
        </template>
    </UiCard>
</template>
