<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { useToast } from '@/composables/useToast';

interface Props {
    user: {
        name: string;
        email: string;
    };
}

const props = defineProps<Props>();

const { showSuccess } = useToast();

// Inertia form for server submission
const form = useForm({
    name: props.user.name,
    email: props.user.email,
});

// Handle PrimeVue Form submission (client-side validation)
function onSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }): void {
    if (valid) {
        Object.assign(form, values);
        form.submit(ProfileController.update(), {
            onSuccess: () => {
                showSuccess('Profile updated successfully.');
            },
        });
    }
}
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
                            autocomplete="username"
                            placeholder="Email address"
                        />
                    </template>
                </UiFormField>

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

