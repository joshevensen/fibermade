<script setup lang="ts">
import {
    destroy as destroyShow,
    update,
} from '@/actions/App/Http/Controllers/ShowController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const initialValues = computed(() => ({
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
}));

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.show.id),
    initialValues: initialValues.value,
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
    <CreatorLayout page-title="Edit Show">
        <template #default>
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <div class="flex flex-col gap-4 lg:flex-row lg:pr-4">
                    <div class="flex-[0_0_60%]">
                        <UiCard>
                            <template #content>
                                <div class="space-y-6">
                                    <UiFormFieldInput
                                        name="name"
                                        label="Name"
                                        placeholder="Show name"
                                        :server-error="form.errors.name"
                                        required
                                    />

                                    <UiFormField
                                        name="description"
                                        label="Description"
                                        :server-error="form.errors.description"
                                    >
                                        <template
                                            #default="{ props: fieldProps }"
                                        >
                                            <UiEditor
                                                v-bind="fieldProps"
                                                placeholder="Show description"
                                            />
                                        </template>
                                    </UiFormField>

                                    <UiFormFieldInput
                                        name="website"
                                        label="Website"
                                        placeholder="https://example.com"
                                        :server-error="form.errors.website"
                                    />

                                    <UiButton
                                        type="submit"
                                        :loading="form.processing"
                                    >
                                        Update Show
                                    </UiButton>
                                </div>
                            </template>
                        </UiCard>
                    </div>

                    <div class="flex-[0_0_40%]">
                        <div class="flex flex-col gap-4">
                            <UiCard>
                                <template #title>Date & Time</template>
                                <template #content>
                                    <div class="space-y-6">
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
                                    </div>
                                </template>
                            </UiCard>

                            <UiCard>
                                <template #title>Location</template>
                                <template #content>
                                    <div class="space-y-6">
                                        <UiFormFieldInput
                                            name="location_name"
                                            label="Location Name"
                                            placeholder="Venue or location name"
                                            :server-error="
                                                form.errors.location_name
                                            "
                                        />

                                        <UiFormFieldAddress
                                            :errors="form.errors"
                                        />
                                    </div>
                                </template>
                            </UiCard>

                            <UiCard>
                                <template #content>
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-sm text-surface-600">
                                                Deleting this show will
                                                permanently remove all
                                                associated data. This action
                                                cannot be undone.
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
                    </div>
                </div>
            </UiForm>
        </template>
    </CreatorLayout>
</template>
