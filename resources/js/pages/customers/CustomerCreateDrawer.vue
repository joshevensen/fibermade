<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/CustomerController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

interface Props {
    visible: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

function closeDrawer(): void {
    emit('update:visible', false);
}

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        email: null,
        phone: null,
        address_line1: null,
        address_line2: null,
        city: null,
        state_region: null,
        postal_code: null,
        country_code: null,
    },
    successMessage: 'Customer created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['customers'] });
    },
});
</script>

<template>
    <UiDrawer
        :visible="visible"
        position="right"
        class="!w-[30rem]"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <h2 class="text-xl font-semibold">Create Customer</h2>
        </template>

        <div class="p-4">
            <UiForm @submit="onSubmit">
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
                    :server-error="form.errors.phone"
                />

                <UiDivider />

                <UiFormFieldAddress :errors="form.errors" />

                <UiButton type="submit" :loading="form.processing">
                    Create Customer
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
