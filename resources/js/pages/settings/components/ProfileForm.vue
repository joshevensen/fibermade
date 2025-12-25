<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';

interface Props {
    user: {
        name: string;
        email: string;
    };
}

const props = defineProps<Props>();

const { form, onSubmit } = useFormSubmission({
    route: ProfileController.update,
    initialValues: {
        name: props.user.name,
        email: props.user.email,
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
                :initialValues="{ name: user.name, email: user.email }"
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

