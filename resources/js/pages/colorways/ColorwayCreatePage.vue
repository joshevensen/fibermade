<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import { store } from '@/actions/App/Http/Controllers/ColorwayController';
import { index } from '@/actions/App/Http/Controllers/ColorwayController';
import { useIcon } from '@/composables/useIcon';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

interface Props {
    colorwayStatusOptions: Array<{
        label: string;
        value: string;
    }>;
    techniqueOptions: Array<{
        label: string;
        value: string;
    }>;
    colorOptions: Array<{
        label: string;
        value: string;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        slug: '',
        description: null,
        technique: null,
        colors: null,
        status: null,
        shopify_product_id: null,
    },
    successMessage: 'Colorway created successfully.',
    onSuccess: () => {
        router.visit(index.url());
    },
});
</script>

<template>
    <AppLayout page-title="Create Colorway">
        <PageHeader
            heading="Create Colorway"
            :icon="IconList.Colorways"
        />

        <div class="mt-6">
            <UiCard>
                <template #title>Colorway Information</template>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Colorway name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldInput
                            name="slug"
                            label="Slug"
                            placeholder="colorway-slug"
                            :server-error="form.errors.slug"
                            required
                        />

                        <UiFormFieldTextarea
                            name="description"
                            label="Description"
                            placeholder="Colorway description"
                            :server-error="form.errors.description"
                        />

                        <UiFormFieldSelect
                            name="technique"
                            label="Technique"
                            :options="techniqueOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select technique"
                            :server-error="form.errors.technique"
                            show-clear
                        />

                        <UiFormFieldMultiSelect
                            name="colors"
                            label="Colors"
                            :options="colorOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select colors"
                            :server-error="form.errors.colors"
                        />

                        <UiFormFieldSelect
                            name="status"
                            label="Status"
                            :options="colorwayStatusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select status"
                            :server-error="form.errors.status"
                            required
                        />

                        <UiFormFieldInput
                            name="shopify_product_id"
                            label="Shopify Product ID"
                            placeholder="Shopify product ID"
                            :server-error="form.errors.shopify_product_id"
                        />

                        <UiButton
                            type="submit"
                            :loading="form.processing"
                        >
                            Create Colorway
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
