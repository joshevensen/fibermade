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
import { update } from '@/actions/App/Http/Controllers/ColorwayController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

interface Props {
    colorway: {
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        technique?: string | null;
        colors?: string[] | null;
        status: string;
        shopify_product_id?: string | null;
    };
    colorwayStatusOptions: Array<{ label: string; value: string }>;
    techniqueOptions: Array<{ label: string; value: string }>;
    colorOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.colorway.id),
    initialValues: {
        name: props.colorway.name || '',
        slug: props.colorway.slug || '',
        description: props.colorway.description || null,
        technique: props.colorway.technique || null,
        colors: props.colorway.colors || null,
        status: props.colorway.status || null,
        shopify_product_id: props.colorway.shopify_product_id || null,
    },
    successMessage: 'Colorway updated successfully.',
    onSuccess: () => {
        router.visit('/colorways');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Colorway">
        <PageHeader
            heading="Edit Colorway"
            :icon="IconList.Colorways"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
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

                <div class="flex gap-4">
                    <UiButton
                        type="submit"
                        :loading="form.processing"
                    >
                        Update Colorway
                    </UiButton>
                    <UiButton
                        type="button"
                        severity="secondary"
                        @click="router.visit('/colorways')"
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
