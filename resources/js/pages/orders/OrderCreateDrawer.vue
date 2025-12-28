<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import { store } from '@/actions/App/Http/Controllers/OrderController';
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
const orderStatusOptions = enumToOptions(orderStatusCases);

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
        type: null,
        status: null,
        shopify_order_id: null,
        order_date: null,
        subtotal_amount: null,
        shipping_amount: null,
        discount_amount: null,
        tax_amount: null,
        total_amount: null,
        notes: null,
    },
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
            <UiForm @submit="onSubmit">
                        <UiFormFieldSelect
                            name="type"
                            label="Type"
                            :options="orderTypeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select order type"
                            :server-error="form.errors.type"
                            required
                        />

                        <UiFormFieldSelect
                            name="status"
                            label="Status"
                            :options="orderStatusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select status"
                            :server-error="form.errors.status"
                            required
                        />

                        <UiFormFieldInput
                            name="shopify_order_id"
                            label="Shopify Order ID"
                            placeholder="Shopify order ID"
                            :server-error="form.errors.shopify_order_id"
                        />

                        <UiFormFieldDatePicker
                            name="order_date"
                            label="Order Date"
                            placeholder="Select order date"
                            :server-error="form.errors.order_date"
                            show-icon
                            required
                        />

                        <UiFormFieldInputNumber
                            name="subtotal_amount"
                            label="Subtotal Amount"
                            :min="0"
                            :server-error="form.errors.subtotal_amount"
                        />

                        <UiFormFieldInputNumber
                            name="shipping_amount"
                            label="Shipping Amount"
                            :min="0"
                            :server-error="form.errors.shipping_amount"
                        />

                        <UiFormFieldInputNumber
                            name="discount_amount"
                            label="Discount Amount"
                            :min="0"
                            :server-error="form.errors.discount_amount"
                        />

                        <UiFormFieldInputNumber
                            name="tax_amount"
                            label="Tax Amount"
                            :min="0"
                            :server-error="form.errors.tax_amount"
                        />

                        <UiFormFieldInputNumber
                            name="total_amount"
                            label="Total Amount"
                            :min="0"
                            :server-error="form.errors.total_amount"
                        />

                        <UiFormFieldTextarea
                            name="notes"
                            label="Notes"
                            placeholder="Order notes"
                            :server-error="form.errors.notes"
                        />

                        <UiButton
                            type="submit"
                            :loading="form.processing"
                        >
                            Create Order
                        </UiButton>
                    </UiForm>
        </div>
    </UiDrawer>
</template>

