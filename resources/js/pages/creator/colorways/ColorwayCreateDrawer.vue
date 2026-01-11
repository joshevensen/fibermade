<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/ColorwayController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
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

const initialValues = {
    name: '',
    technique: null,
    colors: null,
    per_pan: null,
    status: 'idea',
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
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
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormField
                    name="status"
                    label="Status"
                    :server-error="form.errors.status"
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="colorwayStatusOptions"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormFieldInput
                    name="name"
                    label="Name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldSelect
                    name="technique"
                    label="Technique"
                    :options="techniqueOptions"
                    :server-error="form.errors.technique"
                    show-clear
                />

                <UiFormFieldMultiSelect
                    name="colors"
                    label="Colors"
                    :options="colorOptions"
                    :server-error="form.errors.colors"
                />

                <UiFormFieldInputNumber
                    name="per_pan"
                    label="Per Pan"
                    :min="1"
                    :max="6"
                    :server-error="form.errors.per_pan"
                    required
                />

                <UiButton type="submit" :loading="form.processing">
                    Create Colorway
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
