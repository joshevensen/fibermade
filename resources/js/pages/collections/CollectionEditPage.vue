<script setup lang="ts">
import {
    destroy as destroyCollection,
    update,
} from '@/actions/App/Http/Controllers/CollectionController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    collection: {
        id: number;
        name: string;
        description?: string | null;
    };
}

const props = defineProps<Props>();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.collection.id),
    initialValues: {
        name: props.collection.name || '',
        description: props.collection.description || null,
    },
    successMessage: 'Collection updated successfully.',
    onSuccess: () => {
        router.visit('/collections');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.collection.name}?`,
        onAccept: () => {
            router.delete(destroyCollection.url(props.collection.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Collection">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Collection name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldTextarea
                            name="description"
                            label="Description"
                            placeholder="Collection description"
                            :server-error="form.errors.description"
                        />

                        <UiButton type="submit" :loading="form.processing">
                            Update Collection
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
                                    Deleting this collection will permanently
                                    remove all associated data. This action
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
                                Delete Collection
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </AppLayout>
</template>
