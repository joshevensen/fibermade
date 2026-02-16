<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/ShowController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

interface Props {
    visible: boolean;
}

defineProps<Props>();

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
        start_at: null,
        end_at: null,
        location_name: null,
        address_line1: null,
        city: null,
        state_region: null,
        postal_code: null,
        country_code: null,
        website: null,
    },
    successMessage: 'Show created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['shows'] });
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
            <h2 class="text-xl font-semibold">Create Show</h2>
        </template>

        <div class="p-4">
            <UiForm @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    :server-error="form.errors.name"
                    required
                />

                <UiDivider />

                <UiFormFieldInput
                    name="location_name"
                    label="Location Name"
                    :server-error="form.errors.location_name"
                />

                <UiFormFieldAddress :errors="form.errors" />

                <UiDivider />

                <div class="grid grid-cols-2 gap-4">
                    <UiFormFieldDatePicker
                        name="start_at"
                        label="Start Date & Time"
                        :server-error="form.errors.start_at"
                        show-time
                        show-icon
                        required
                    />

                    <UiFormFieldDatePicker
                        name="end_at"
                        label="End Date & Time"
                        :server-error="form.errors.end_at"
                        show-time
                        show-icon
                        required
                    />
                </div>

                <UiFormFieldInput
                    name="website"
                    label="Website"
                    :server-error="form.errors.website"
                />

                <UiButton type="submit" :loading="form.processing">
                    Create Show
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
