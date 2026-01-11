<!-- TODO: Restore edit functionality when ready to work on orders -->
<script setup lang="ts">
import { edit as editCustomer } from '@/actions/App/Http/Controllers/CustomerController';
// TODO: Re-enable these imports when ready to work on orders
// import {
//     destroy as destroyOrder,
//     update,
// } from '@/actions/App/Http/Controllers/OrderController';
// import { destroy as destroyOrderItem } from '@/actions/App/Http/Controllers/OrderItemController';
import { edit as editShow } from '@/actions/App/Http/Controllers/ShowController';
import { edit as editStore } from '@/actions/App/Http/Controllers/StoreController';
// import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
// import UiEditor from '@/components/ui/UiEditor.vue';
// import UiForm from '@/components/ui/UiForm.vue';
// import UiFormField from '@/components/ui/UiFormField.vue';
// import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
// import UiSelectButton from '@/components/ui/UiSelectButton.vue';
// import { useConfirm } from '@/composables/useConfirm';
// import { useFormSubmission } from '@/composables/useFormSubmission';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
// import OrderItemDrawer from '@/pages/creator/orders/components/OrderItemDrawer.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface OrderItem {
    id: number;
    colorway_id: number;
    base_id: number;
    quantity: number;
    unit_price?: number | null;
    line_total?: number | null;
    colorway?: {
        id: number;
        name: string;
    } | null;
    base?: {
        id: number;
        code: string;
        descriptor: string;
    } | null;
}

interface OrderableShow {
    id: number;
    name: string;
    location_name?: string | null;
    start_at?: string | null;
    end_at?: string | null;
}

interface OrderableStore {
    id: number;
    name: string;
    email?: string | null;
    city?: string | null;
    state_region?: string | null;
}

interface OrderableCustomer {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    city?: string | null;
    state_region?: string | null;
}

interface Props {
    order: {
        id: number;
        type: string;
        status: string;
        order_date: string;
        shipping_amount?: number | null;
        discount_amount?: number | null;
        tax_amount?: number | null;
        notes?: string | null;
        orderItems?: OrderItem[];
        orderable?: OrderableShow | OrderableStore | OrderableCustomer | null;
    };
    orderTypeOptions: Array<{ label: string; value: string }>;
    orderStatusOptions: Array<{ label: string; value: string }>;
    colorways: Array<{ id: number; name: string }>;
    bases: Array<{ id: number; code: string; descriptor: string }>;
}

const props = defineProps<Props>();
// TODO: Re-enable delete functionality when ready to work on orders
// const { requireDelete } = useConfirm();

// TODO: Re-enable OrderItemDrawer functionality when ready to work on orders
// const showOrderItemDrawer = ref(false);
// const editingOrderItem = ref<OrderItem | null>(null);

// function openAddItemDrawer(): void {
//     editingOrderItem.value = null;
//     showOrderItemDrawer.value = true;
// }

// function openEditItemDrawer(item: OrderItem): void {
//     editingOrderItem.value = item;
//     showOrderItemDrawer.value = true;
// }

// function closeOrderItemDrawer(): void {
//     showOrderItemDrawer.value = false;
//     editingOrderItem.value = null;
// }

// function handleDeleteItem(event: Event, item: OrderItem): void {
//     requireDelete({
//         target: event.currentTarget as HTMLElement,
//         message: 'Are you sure you want to delete this order item?',
//         onAccept: () => {
//             router.delete(destroyOrderItem.url(item.id), {
//                 onSuccess: () => {
//                     router.reload({ only: ['order'] });
//                 },
//             });
//         },
//     });
// }

const calculatedTotals = computed(() => {
    const subtotal =
        props.order.orderItems?.reduce(
            (sum, item) => sum + (item.line_total || 0),
            0,
        ) || 0;
    const shipping = props.order.shipping_amount || 0;
    const discount = props.order.discount_amount || 0;
    const tax = props.order.tax_amount || 0;
    const total = subtotal + shipping - discount + tax;

    return {
        subtotal,
        shipping,
        discount,
        tax,
        total,
    };
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
}

function formatStatus(status: string): string {
    return status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function getStatusBadgeClass(status: string): string {
    const statusMap: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800',
        open: 'bg-blue-100 text-blue-800',
        closed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return statusMap[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
}

// TODO: Re-enable form submission when ready to work on orders
// const { form, onSubmit } = useFormSubmission({
//     route: () => update(props.order.id),
//     initialValues: {
//         status: props.order.status || null,
//         order_date: props.order.order_date || null,
//         notes: props.order.notes || null,
//     },
//     successMessage: 'Order updated successfully.',
//     onSuccess: () => {
//         router.visit('/orders');
//     },
// });

// TODO: Re-enable delete handler when ready to work on orders
// function handleDelete(event: Event): void {
//     requireDelete({
//         target: event.currentTarget as HTMLElement,
//         message: 'Are you sure you want to delete this order?',
//         onAccept: () => {
//             router.delete(destroyOrder.url(props.order.id));
//         },
//     });
// }
</script>

<template>
    <CreatorLayout page-title="Order Details">
        <template #default>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #content>
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Status
                                </label>
                                <span
                                    class="inline-block rounded-full px-3 py-1 text-sm font-medium"
                                    :class="
                                        getStatusBadgeClass(props.order.status)
                                    "
                                >
                                    {{ formatStatus(props.order.status) }}
                                </span>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Order Date
                                </label>
                                <p class="text-base text-surface-900">
                                    {{ formatDate(props.order.order_date) }}
                                </p>
                            </div>

                            <div v-if="props.order.notes">
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Notes
                                </label>
                                <div
                                    class="prose prose-sm max-w-none text-base text-surface-900"
                                    v-html="props.order.notes"
                                />
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #header>
                        <h3 class="text-lg font-semibold">Order Items</h3>
                    </template>
                    <template #content>
                        <div
                            v-if="
                                !order.orderItems ||
                                order.orderItems.length === 0
                            "
                            class="py-8 text-center text-surface-500"
                        >
                            No order items
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-surface-200">
                                        <th
                                            class="px-4 py-2 text-left text-sm font-semibold text-surface-700"
                                        >
                                            Colorway
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-sm font-semibold text-surface-700"
                                        >
                                            Base
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Quantity
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Unit Price
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Line Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in order.orderItems"
                                        :key="item.id"
                                        class="border-b border-surface-100"
                                    >
                                        <td class="px-4 py-2 text-sm">
                                            {{ item.colorway?.name || 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ item.base?.code || 'N/A' }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm"
                                        >
                                            {{ item.quantity }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm"
                                        >
                                            {{
                                                item.unit_price
                                                    ? formatCurrency(
                                                          item.unit_price,
                                                      )
                                                    : 'N/A'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm font-medium"
                                        >
                                            {{
                                                item.line_total
                                                    ? formatCurrency(
                                                          item.line_total,
                                                      )
                                                    : 'N/A'
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard v-if="order.orderable">
                    <template #header>
                        <h3 class="text-lg font-semibold">
                            {{
                                order.type === 'show'
                                    ? 'Show'
                                    : order.type === 'wholesale'
                                      ? 'Store'
                                      : 'Customer'
                            }}
                        </h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-if="order.type === 'show'">
                                <Link
                                    :href="
                                        editShow.url(
                                            (order.orderable as OrderableShow)
                                                .id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableShow).name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableShow)
                                            .location_name
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableShow)
                                            .location_name
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableShow)
                                            .start_at
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        new Date(
                                            (order.orderable as OrderableShow)
                                                .start_at!,
                                        ).toLocaleDateString()
                                    }}
                                    <span
                                        v-if="
                                            (order.orderable as OrderableShow)
                                                .end_at
                                        "
                                    >
                                        -
                                        {{
                                            new Date(
                                                (
                                                    order.orderable as OrderableShow
                                                ).end_at!,
                                            ).toLocaleDateString()
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="order.type === 'wholesale'">
                                <Link
                                    :href="
                                        editStore.url(
                                            (order.orderable as OrderableStore)
                                                .id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableStore).name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableStore)
                                            .email
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableStore)
                                            .email
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableStore)
                                            .city ||
                                        (order.orderable as OrderableStore)
                                            .state_region
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        [
                                            (order.orderable as OrderableStore)
                                                .city,
                                            (order.orderable as OrderableStore)
                                                .state_region,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </div>
                            </div>
                            <div v-else>
                                <Link
                                    :href="
                                        editCustomer.url(
                                            (
                                                order.orderable as OrderableCustomer
                                            ).id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .email
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .email
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .phone
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .phone
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .city ||
                                        (order.orderable as OrderableCustomer)
                                            .state_region
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        [
                                            (
                                                order.orderable as OrderableCustomer
                                            ).city,
                                            (
                                                order.orderable as OrderableCustomer
                                            ).state_region,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </div>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #header>
                        <h3 class="text-lg font-semibold">Totals</h3>
                    </template>
                    <template #content>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Subtotal:</span>
                                <span class="font-medium">
                                    {{
                                        formatCurrency(
                                            calculatedTotals.subtotal,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Shipping:</span>
                                <span class="font-medium">
                                    {{
                                        formatCurrency(
                                            calculatedTotals.shipping,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Discount:</span>
                                <span class="font-medium text-red-600">
                                    -{{
                                        formatCurrency(
                                            calculatedTotals.discount,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Tax:</span>
                                <span class="font-medium">
                                    {{ formatCurrency(calculatedTotals.tax) }}
                                </span>
                            </div>
                            <div class="border-t border-surface-200 pt-2">
                                <div class="flex justify-between">
                                    <span class="text-base font-semibold"
                                        >Total:</span
                                    >
                                    <span class="text-lg font-bold">
                                        {{
                                            formatCurrency(
                                                calculatedTotals.total,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <!-- TODO: Re-enable delete functionality when ready to work on orders -->
                <!-- <UiCard>
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
                </UiCard> -->
            </div>
        </template>
    </CreatorLayout>

    <!-- TODO: Re-enable OrderItemDrawer when ready to work on orders -->
    <!-- <OrderItemDrawer
        :visible="showOrderItemDrawer"
        :order-id="order.id"
        :order-item="editingOrderItem"
        :colorways="colorways"
        :bases="bases"
        @update:visible="closeOrderItemDrawer"
    /> -->
</template>
