<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/CollectionController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    collection: {
        id: number;
        name: string;
        slug: string;
        description?: string | null;
    };
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.collection.id),
    initialValues: {
        name: props.collection.name || '',
        slug: props.collection.slug || '',
        description: props.collection.description || null,
    },
    successMessage: 'Collection updated successfully.',
    onSuccess: () => {
        router.visit('/collections');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Collection">
        <PageHeader
            heading="Edit Collection"
            :business-icon="BusinessIconList.Collections"
        />

        <div class="mt-6 max-w-2xl">
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

                        <UiFormFieldInput
                            name="slug"
                            label="Slug"
                            placeholder="collection-slug"
                            :server-error="form.errors.slug"
                            required
                        />

                        <UiFormFieldTextarea
                            name="description"
                            label="Description"
                            placeholder="Collection description"
                            :server-error="form.errors.description"
                        />

                        <div class="flex gap-4">
                            <UiButton type="submit" :loading="form.processing">
                                Update Collection
                            </UiButton>
                            <UiButton
                                type="button"
                                severity="secondary"
                                @click="router.visit('/collections')"
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
