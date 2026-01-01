<script setup lang="ts">
import {
    destroy as destroyDye,
    update,
} from '@/actions/App/Http/Controllers/DyeController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    dye: {
        id: number;
        name: string;
        notes?: string | null;
        does_bleed: boolean;
        do_like: boolean;
    };
}

const props = defineProps<Props>();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.dye.id),
    initialValues: {
        name: props.dye.name || '',
        notes: props.dye.notes || null,
        does_bleed: props.dye.does_bleed ?? false,
        do_like: props.dye.do_like ?? false,
    },
    successMessage: 'Dye updated successfully.',
    onSuccess: () => {
        router.visit('/dyes');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.dye.name}?`,
        onAccept: () => {
            router.delete(destroyDye.url(props.dye.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Dye">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Dye name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldTextarea
                            name="notes"
                            label="Notes"
                            placeholder="Dye notes"
                            :server-error="form.errors.notes"
                        />

                        <UiFormFieldCheckbox
                            name="does_bleed"
                            label="Does Bleed"
                            :server-error="form.errors.does_bleed"
                            binary
                        />

                        <UiFormFieldCheckbox
                            name="do_like"
                            label="Do Like"
                            :server-error="form.errors.do_like"
                            binary
                        />

                        <UiButton type="submit" :loading="form.processing">
                            Update Dye
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
                                    Deleting this dye will permanently remove
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
                                Delete Dye
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </AppLayout>
</template>
