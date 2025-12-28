<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import { update } from '@/actions/App/Http/Controllers/OrderController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    order: {
        id: number;
        type: string;
        status: string;
        account_id: number;
        shopify_order_id?: string | null;
        order_date: string;
        subtotal_amount?: number | null;
        shipping_amount?: number | null;
        discount_amount?: number | null;
        tax_amount?: number | null;
        total_amount?: number | null;
        notes?: string | null;
    };
    orderTypeOptions: Array<{ label: string; value: string }>;
    orderStatusOptions: Array<{ label: string; value: string }>;
    accounts: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();

const accountOptions = computed(() =>
    props.accounts.map((account) => ({
        label: account.name,
        value: account.id.toString(),
    }))
);

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.order.id),
    initialValues: {
        type: props.order.type || null,
        status: props.order.status || null,
        account_id: props.order.account_id?.toString() || null,
        shopify_order_id: props.order.shopify_order_id || null,
        order_date: props.order.order_date || null,
        subtotal_amount: props.order.subtotal_amount || null,
        shipping_amount: props.order.shipping_amount || null,
        discount_amount: props.order.discount_amount || null,
        tax_amount: props.order.tax_amount || null,
        total_amount: props.order.total_amount || null,
        notes: props.order.notes || null,
    },
    transform: (values) => {
        const transformed = { ...values };
        // Convert account_id back to integer
        if (transformed.account_id) {
            transformed.account_id = parseInt(transformed.account_id, 10);
        }
        return transformed;
    },
    successMessage: 'Order updated successfully.',
    onSuccess: () => {
        router.visit('/orders');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Order">
        <PageHeader
            heading="Edit Order"
            :icon="IconList.Orders"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
                <template #content>
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
                    placeholder="Select order status"
                    :server-error="form.errors.status"
                    required
                />

                <UiFormFieldSelect
                    name="account_id"
                    label="Account"
                    :options="accountOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select account"
                    :server-error="form.errors.account_id"
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
                    :max="99999999.99"
                    :server-error="form.errors.subtotal_amount"
                />

                <UiFormFieldInputNumber
                    name="shipping_amount"
                    label="Shipping Amount"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.shipping_amount"
                />

                <UiFormFieldInputNumber
                    name="discount_amount"
                    label="Discount Amount"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.discount_amount"
                />

                <UiFormFieldInputNumber
                    name="tax_amount"
                    label="Tax Amount"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.tax_amount"
                />

                <UiFormFieldInputNumber
                    name="total_amount"
                    label="Total Amount"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.total_amount"
                />

                <UiFormFieldTextarea
                    name="notes"
                    label="Notes"
                    placeholder="Order notes"
                    :server-error="form.errors.notes"
                />

                <div class="flex gap-4">
                    <UiButton
                        type="submit"
                        :loading="form.processing"
                    >
                        Update Order
                    </UiButton>
                    <UiButton
                        type="button"
                        severity="secondary"
                        @click="router.visit('/orders')"
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
