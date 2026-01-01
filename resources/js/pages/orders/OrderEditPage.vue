<script setup lang="ts">
import {
    destroy as destroyOrder,
    update,
} from '@/actions/App/Http/Controllers/OrderController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    order: {
        id: number;
        type: string;
        status: string;
        account_id: number;
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
const { requireDelete } = useConfirm();

const accountOptions = computed(() =>
    props.accounts.map((account) => ({
        label: account.name,
        value: account.id.toString(),
    })),
);

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.order.id),
    initialValues: {
        type: props.order.type || null,
        status: props.order.status || null,
        account_id: props.order.account_id?.toString() || null,
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

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: 'Are you sure you want to delete this order?',
        onAccept: () => {
            router.delete(destroyOrder.url(props.order.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Order">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldSelect
                            name="type"
                            label="Type"
                            :options="orderTypeOptions"
                            placeholder="Select order type"
                            :server-error="form.errors.type"
                            required
                        />

                        <UiFormFieldSelect
                            name="status"
                            label="Status"
                            :options="orderStatusOptions"
                            placeholder="Select order status"
                            :server-error="form.errors.status"
                            required
                        />

                        <UiFormFieldSelect
                            name="account_id"
                            label="Account"
                            :options="accountOptions"
                            placeholder="Select account"
                            :server-error="form.errors.account_id"
                            required
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

                        <UiButton type="submit" :loading="form.processing">
                            Update Order
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this order will permanently remove
                                    all associated data. This action cannot be
                                    undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Order
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </AppLayout>
</template>
