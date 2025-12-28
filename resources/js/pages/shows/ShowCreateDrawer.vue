<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/ShowController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
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
        start_at: null,
        end_at: null,
        location_name: null,
        location_address: null,
        location_city: null,
        location_state: null,
        location_zip: null,
        description: null,
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
                    placeholder="Show name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldDatePicker
                    name="start_at"
                    label="Start Date & Time"
                    placeholder="Select start date and time"
                    :server-error="form.errors.start_at"
                    show-time
                    show-icon
                    required
                />

                <UiFormFieldDatePicker
                    name="end_at"
                    label="End Date & Time"
                    placeholder="Select end date and time"
                    :server-error="form.errors.end_at"
                    show-time
                    show-icon
                    required
                />

                <UiFormFieldInput
                    name="location_name"
                    label="Location Name"
                    placeholder="Venue or location name"
                    :server-error="form.errors.location_name"
                />

                <UiFormFieldInput
                    name="location_address"
                    label="Address"
                    placeholder="Street address"
                    :server-error="form.errors.location_address"
                />

                <div class="grid grid-cols-2 gap-4">
                    <UiFormFieldInput
                        name="location_city"
                        label="City"
                        placeholder="City"
                        :server-error="form.errors.location_city"
                    />

                    <UiFormFieldInput
                        name="location_state"
                        label="State"
                        placeholder="State"
                        :server-error="form.errors.location_state"
                    />
                </div>

                <UiFormFieldInput
                    name="location_zip"
                    label="ZIP Code"
                    placeholder="ZIP code"
                    :server-error="form.errors.location_zip"
                />

                <UiFormFieldTextarea
                    name="description"
                    label="Description"
                    placeholder="Show description"
                    :server-error="form.errors.description"
                />

                <UiFormFieldInput
                    name="website"
                    label="Website"
                    placeholder="https://example.com"
                    :server-error="form.errors.website"
                />

                <UiButton type="submit" :loading="form.processing">
                    Create Show
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
