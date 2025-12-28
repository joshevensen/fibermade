<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/ShowController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    show: {
        id: number;
        name: string;
        start_at: string;
        end_at: string;
        location_name?: string | null;
        location_address?: string | null;
        location_city?: string | null;
        location_state?: string | null;
        location_zip?: string | null;
        description?: string | null;
        website?: string | null;
    };
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.show.id),
    initialValues: {
        name: props.show.name || '',
        start_at: props.show.start_at ? new Date(props.show.start_at) : null,
        end_at: props.show.end_at ? new Date(props.show.end_at) : null,
        location_name: props.show.location_name || null,
        location_address: props.show.location_address || null,
        location_city: props.show.location_city || null,
        location_state: props.show.location_state || null,
        location_zip: props.show.location_zip || null,
        description: props.show.description || null,
        website: props.show.website || null,
    },
    successMessage: 'Show updated successfully.',
    onSuccess: () => {
        router.visit('/shows');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Show">
        <PageHeader
            heading="Edit Show"
            :business-icon="BusinessIconList.Shows"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
                <template #content>
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

                        <div class="flex gap-4">
                            <UiButton type="submit" :loading="form.processing">
                                Update Show
                            </UiButton>
                            <UiButton
                                type="button"
                                severity="secondary"
                                @click="router.visit('/shows')"
                            >
                                Cancel
                            </UiButton>
                        </div>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
