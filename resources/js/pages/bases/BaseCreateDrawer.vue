<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/BaseController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { enumToOptions } from '@/utils/enumOptions';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enums
const baseStatusCases = [
    { name: 'Active', value: 'active' },
    { name: 'Retired', value: 'retired' },
];

const weightCases = [
    { name: 'Lace', value: 'lace' },
    { name: 'Fingering', value: 'fingering' },
    { name: 'DK', value: 'dk' },
    { name: 'Worsted', value: 'worsted' },
    { name: 'Bulky', value: 'bulky' },
];

const baseStatusOptions = enumToOptions(baseStatusCases);
const weightOptions = enumToOptions(weightCases);

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
        slug: '',
        description: null,
        status: null,
        weight: null,
        descriptor: '',
        size: null,
        cost: null,
        retail_price: null,
    },
    successMessage: 'Base created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['bases'] });
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
            <h2 class="text-xl font-semibold">Create Base</h2>
        </template>

        <div class="p-4">
            <UiForm @submit="onSubmit">
                <UiFormField
                    name="status"
                    label="Status"
                    :server-error="form.errors.status"
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="baseStatusOptions"
                            option-label="label"
                            option-value="value"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormFieldInput
                    name="descriptor"
                    label="Descriptor"
                    placeholder="Base descriptor"
                    :server-error="form.errors.descriptor"
                    required
                />

                <UiFormFieldInput
                    name="slug"
                    label="Slug"
                    placeholder="base-slug"
                    :server-error="form.errors.slug"
                    required
                />

                <UiFormField
                    name="description"
                    label="Description"
                    :server-error="form.errors.description"
                >
                    <template #default="{ props: fieldProps }">
                        <UiEditor
                            v-bind="fieldProps"
                            placeholder="Base description"
                        />
                    </template>
                </UiFormField>

                <UiFormFieldSelect
                    name="weight"
                    label="Weight"
                    :options="weightOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select weight"
                    :server-error="form.errors.weight"
                    show-clear
                />

                <UiFormField
                    name="size"
                    label="Size"
                    :server-error="form.errors.size"
                >
                    <template #default="{ props: fieldProps }">
                        <UiInputGroup>
                            <UiInputNumber v-bind="fieldProps" :min="0" />
                            <UiInputGroupAddon>grams</UiInputGroupAddon>
                        </UiInputGroup>
                    </template>
                </UiFormField>

                <UiFormField
                    name="cost"
                    label="Cost"
                    :server-error="form.errors.cost"
                >
                    <template #default="{ props: fieldProps }">
                        <UiInputGroup>
                            <UiInputGroupAddon>$</UiInputGroupAddon>
                            <UiInputNumber
                                v-bind="fieldProps"
                                :min="0"
                                :max="99999999.99"
                                :step="0.01"
                            />
                        </UiInputGroup>
                    </template>
                </UiFormField>

                <UiFormField
                    name="retail_price"
                    label="Retail Price"
                    :server-error="form.errors.retail_price"
                >
                    <template #default="{ props: fieldProps }">
                        <UiInputGroup>
                            <UiInputGroupAddon>$</UiInputGroupAddon>
                            <UiInputNumber
                                v-bind="fieldProps"
                                :min="0"
                                :max="99999999.99"
                                :step="0.01"
                            />
                        </UiInputGroup>
                    </template>
                </UiFormField>

                <UiButton type="submit" :loading="form.processing">
                    Create Base
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
