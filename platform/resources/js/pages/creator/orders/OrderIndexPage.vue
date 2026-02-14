<script setup lang="ts">
import { edit as editOrder } from '@/actions/App/Http/Controllers/OrderController';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { orderStatusBadgeClass } from '@/utils/orderStatusBadge';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    orders: Array<{
        id: number;
        type: string;
        status: string;
        shopify_order_id?: string | null;
        order_date: string;
        subtotal_amount?: number | null;
        shipping_amount?: number | null;
        discount_amount?: number | null;
        tax_amount?: number | null;
        total_amount?: number | null;
        notes?: string | null;
        orderable?: {
            name: string;
        } | null;
        orderItems?: Array<{
            quantity: number;
        }>;
    }>;
    orderTypeOptions: Array<{ label: string; value: string }>;
    orderStatusOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();

// Filter state
const typeFilter = ref<string | 'all'>('all');
const statusFilter = ref<string | 'all'>('all');

// Add "All" option to filters
const typeFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.orderTypeOptions,
];

const statusFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.orderStatusOptions,
];

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
}

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function getOrderableName(order: Props['orders'][0]): string {
    return order.orderable?.name || '';
}

function getSkeinCount(order: Props['orders'][0]): number {
    if (!order.orderItems || order.orderItems.length === 0) {
        return 0;
    }
    return order.orderItems.reduce(
        (sum, item) => sum + (item.quantity || 0),
        0,
    );
}

// Filter orders
const filteredOrders = computed(() => {
    let filtered = [...props.orders];

    // Apply type filter
    if (typeFilter.value !== 'all') {
        filtered = filtered.filter((order) => order.type === typeFilter.value);
    }

    // Apply status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(
            (order) => order.status === statusFilter.value,
        );
    }

    return filtered;
});

const columns = computed(() => [
    {
        field: 'orderable.name',
        header: 'Name',
        sortable: true,
        columnKey: 'name',
    },
    {
        field: 'type',
        header: 'Type',
        sortable: true,
        columnKey: 'type',
        bodyTemplate: (data: Props['orders'][0]) => formatEnum(data.type),
    },
    {
        field: 'status',
        header: 'Status',
        sortable: true,
        columnKey: 'status',
    },
    {
        field: 'order_date',
        header: 'Order Date',
        sortable: true,
        columnKey: 'order_date',
        bodyTemplate: (data: Props['orders'][0]) => formatDate(data.order_date),
    },
    {
        header: '# Skeins',
        sortable: false,
        columnKey: 'skeins',
        bodyTemplate: (data: Props['orders'][0]) =>
            getSkeinCount(data).toString(),
    },
    {
        field: 'total_amount',
        header: 'Total',
        sortable: true,
        columnKey: 'total_amount',
        bodyTemplate: (data: Props['orders'][0]) =>
            formatCurrency(data.total_amount),
    },
]);
</script>

<template>
    <CreatorLayout page-title="Orders">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.orders.length"
                    :filtered-count="filteredOrders.length"
                    label="order"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="type-filter"
                            label="Type"
                            label-position="left"
                            :options="typeFilterOptions"
                            :initial-value="typeFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="typeFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="status-filter"
                            label="Status"
                            label-position="left"
                            :options="statusFilterOptions"
                            :initial-value="statusFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="statusFilter = $event"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <UiDataTable
                    :value="filteredOrders"
                    :columns="columns"
                    data-key="id"
                    striped-rows
                    show-gridlines
                    paginator
                    :rows="20"
                    empty-message="No orders found"
                >
                    <template #name="{ data }">
                        <button
                            type="button"
                            class="cursor-pointer text-primary hover:underline"
                            @click="router.visit(editOrder.url(data.id))"
                        >
                            {{ getOrderableName(data) || '—' }}
                        </button>
                    </template>
                    <template #status="{ data }">
                        <span
                            class="inline-block rounded-full px-3 py-1 text-sm font-medium"
                            :class="orderStatusBadgeClass(data.status)"
                        >
                            {{ formatEnum(data.status) }}
                        </span>
                    </template>
                </UiDataTable>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
