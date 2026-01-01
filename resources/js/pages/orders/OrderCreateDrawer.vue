<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/OrderController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { enumToOptions } from '@/utils/enumOptions';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enums
const orderTypeCases = [
    { name: 'Wholesale', value: 'wholesale' },
    { name: 'Retail', value: 'retail' },
    { name: 'Show', value: 'show' },
];

const orderStatusCases = [
    { name: 'Draft', value: 'draft' },
    { name: 'Open', value: 'open' },
    { name: 'Closed', value: 'closed' },
    { name: 'Cancelled', value: 'cancelled' },
];

const orderTypeOptions = enumToOptions(orderTypeCases);
const orderStatusOptions = enumToOptions(
    orderStatusCases.filter((status) => status.value !== 'cancelled'),
);

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
    type: 'wholesale',
    status: 'draft',
    order_date: null,
    notes: null,
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    successMessage: 'Order created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['orders'] });
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
            <h2 class="text-xl font-semibold">Create Order</h2>
        </template>

        <div class="p-4">
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormField
                    name="type"
                    label="Type"
                    :server-error="form.errors.type"
                    required
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="orderTypeOptions"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormField
                    name="status"
                    label="Status"
                    :server-error="form.errors.status"
                    required
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="orderStatusOptions"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormFieldDatePicker
                    name="order_date"
                    label="Order Date"
                    :server-error="form.errors.order_date"
                    show-icon
                    required
                />

                <UiFormField
                    name="notes"
                    label="Notes"
                    :server-error="form.errors.notes"
                >
                    <template #default="{ props: fieldProps }">
                        <UiEditor v-bind="fieldProps" />
                    </template>
                </UiFormField>

                <UiButton type="submit" :loading="form.processing">
                    Create Order
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
