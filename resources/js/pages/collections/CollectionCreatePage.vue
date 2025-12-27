<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { store } from '@/actions/App/Http/Controllers/CollectionController';
import { index } from '@/actions/App/Http/Controllers/CollectionController';
import { useIcon } from '@/composables/useIcon';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

const props = defineProps();
const { IconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        slug: '',
        description: null,
    },
    successMessage: 'Collection created successfully.',
    onSuccess: () => {
        router.visit(index.url());
    },
});
</script>

<template>
    <AppLayout page-title="Create Collection">
        <PageHeader
            heading="Create Collection"
            :icon="IconList.Collections"
        />

        <div class="mt-6">
            <UiCard>
                <template #title>Collection Information</template>
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

                        <UiButton
                            type="submit"
                            :loading="form.processing"
                        >
                            Create Collection
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
