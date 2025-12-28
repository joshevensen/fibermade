<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import { store } from '@/actions/App/Http/Controllers/ColorwayController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { enumToOptions } from '@/utils/enumOptions';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enums
const colorwayStatusCases = [
    { name: 'Idea', value: 'idea' },
    { name: 'Active', value: 'active' },
    { name: 'Retired', value: 'retired' },
];

const techniqueCases = [
    { name: 'Solid', value: 'solid' },
    { name: 'Tonal', value: 'tonal' },
    { name: 'Variegated', value: 'variegated' },
    { name: 'Speckled', value: 'speckled' },
    { name: 'Other', value: 'other' },
];

const colorCases = [
    { name: 'Red', value: 'red' },
    { name: 'Orange', value: 'orange' },
    { name: 'Yellow', value: 'yellow' },
    { name: 'Green', value: 'green' },
    { name: 'Blue', value: 'blue' },
    { name: 'Purple', value: 'purple' },
    { name: 'Pink', value: 'pink' },
    { name: 'Brown', value: 'brown' },
    { name: 'Black', value: 'black' },
    { name: 'White', value: 'white' },
    { name: 'Gray', value: 'gray' },
    { name: 'Teal', value: 'teal' },
    { name: 'Maroon', value: 'maroon' },
    { name: 'Navy', value: 'navy' },
    { name: 'Beige', value: 'beige' },
    { name: 'Tan', value: 'tan' },
    { name: 'Coral', value: 'coral' },
    { name: 'Turquoise', value: 'turquoise' },
];

const colorwayStatusOptions = enumToOptions(colorwayStatusCases);
const techniqueOptions = enumToOptions(techniqueCases);
const colorOptions = enumToOptions(colorCases);

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
        slug: '',
        description: null,
        technique: null,
        colors: null,
        status: null,
        shopify_product_id: null,
    },
    successMessage: 'Colorway created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['colorways'] });
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
            <h2 class="text-xl font-semibold">Create Colorway</h2>
        </template>

        <div class="p-4">
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
        </div>
    </UiDrawer>
</template>

