<script setup lang="ts">
import {
    destroy as destroyShow,
    update,
} from '@/actions/App/Http/Controllers/ShowController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    show: {
        id: number;
        name: string;
        start_at: string;
        end_at: string;
        location_name?: string | null;
        address_line1?: string | null;
        city?: string | null;
        state_region?: string | null;
        postal_code?: string | null;
        country_code?: string | null;
        description?: string | null;
        website?: string | null;
    };
}

const props = defineProps<Props>();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.show.id),
    initialValues: {
        name: props.show.name || '',
        start_at: props.show.start_at ? new Date(props.show.start_at) : null,
        end_at: props.show.end_at ? new Date(props.show.end_at) : null,
        location_name: props.show.location_name || null,
        address_line1: props.show.address_line1 || null,
        city: props.show.city || null,
        state_region: props.show.state_region || null,
        postal_code: props.show.postal_code || null,
        country_code: props.show.country_code || null,
        description: props.show.description || null,
        website: props.show.website || null,
    },
    successMessage: 'Show updated successfully.',
    onSuccess: () => {
        router.visit('/shows');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.show.name}?`,
        onAccept: () => {
            router.delete(destroyShow.url(props.show.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Show">
        <template #default>
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

                        <UiFormFieldAddress :errors="form.errors" />

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
                            Update Show
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this show will permanently remove
                                    all associated data. This action cannot be
                                    undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Show
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </AppLayout>
</template>
