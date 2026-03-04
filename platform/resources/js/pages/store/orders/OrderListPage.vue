<script setup lang="ts">
import PageFilter from '@/components/PageFilter.vue';
import CreatorPageHeader from '@/components/store/CreatorPageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiLink from '@/components/ui/UiLink.vue';
import UiTag from '@/components/ui/UiTag.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Order {
    id: number;
    order_date: string;
    status: string;
    total_amount: number | null;
    skein_count: number;
    colorway_count: number;
}

interface Props {
    creator: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        address_line1: string | null;
        address_line2: string | null;
        city: string | null;
        state_region: string | null;
        postal_code: string | null;
    };
    orders: Order[];
    orderStatusOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();

const getInitialStatusFilter = (): string => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('status') || 'all';
    }
    return 'all';
};

const statusFilter = ref<string>(getInitialStatusFilter());

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

function getOrderStatusSeverity(
    status: string,
): 'success' | 'info' | 'secondary' | 'warn' | 'danger' | 'contrast' {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'open':
            return 'info';
        case 'accepted':
            return 'info';
        case 'fulfilled':
            return 'success';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function handleNewOrder(): void {
    router.visit(`/store/${props.creator.id}/order`);
}

function handleStatusFilterChange(value: string): void {
    statusFilter.value = value;
    router.get(
        `/store/${props.creator.id}/orders`,
        { status: value },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['orders'],
        },
    );
}

const columns = computed(() => [
    {
        field: 'order_date',
        header: 'Order Date',
        sortable: false,
        columnKey: 'order_date',
        bodyTemplate: (data: Order) => formatDate(data.order_date),
    },
    {
        field: 'total_amount',
        header: 'Total',
        sortable: false,
        columnKey: 'total_amount',
        bodyTemplate: (data: Order) => formatCurrency(data.total_amount),
    },
    {
        field: 'status',
        header: 'Status',
        sortable: false,
        columnKey: 'status',
    },
    {
        header: 'Skeins',
        sortable: false,
        columnKey: 'skein_count',
        bodyTemplate: (data: Order) => data.skein_count.toString(),
    },
    {
        header: 'Colorways',
        sortable: false,
        columnKey: 'colorway_count',
        bodyTemplate: (data: Order) => data.colorway_count.toString(),
    },
]);
</script>

<template>
    <StoreLayout :page-title="`Orders — ${props.creator.name}`">
        <CreatorPageHeader :creator="props.creator">
            <UiButton label="New Order" size="large" @click="handleNewOrder" />
        </CreatorPageHeader>

        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.orders.length"
                    :filtered-count="props.orders.length"
                    label="order"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="status-filter"
                            label="Status"
                            label-position="left"
                            :options="statusFilterOptions"
                            :initial-value="statusFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-40"
                            @update:model-value="handleStatusFilterChange"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <div
                    v-if="props.orders.length === 0"
                    class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                >
                    No orders yet
                </div>

                <UiDataTable
                    v-else
                    :value="props.orders"
                    :columns="columns"
                    data-key="id"
                    striped-rows
                    show-gridlines
                    paginator
                    :rows="20"
                    empty-message="No orders found"
                >
                    <template #status="{ data }">
                        <UiTag
                            :severity="getOrderStatusSeverity(data.status)"
                            :value="formatEnum(data.status)"
                        />
                    </template>
                    <template #actions="{ data }">
                        <UiLink
                            v-if="data.status === 'draft'"
                            :href="`/store/${props.creator.id}/order/${data.id}`"
                            class="text-primary hover:underline"
                        >
                            Continue Order
                        </UiLink>
                        <UiLink
                            v-else
                            :href="`/store/orders/${data.id}`"
                            class="text-primary hover:underline"
                        >
                            View Order
                        </UiLink>
                    </template>
                </UiDataTable>
            </template>
        </UiCard>
    </StoreLayout>
</template>
