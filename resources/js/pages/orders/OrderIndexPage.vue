<script setup lang="ts">
import {
    destroy as destroyOrder,
    edit as editOrder,
} from '@/actions/App/Http/Controllers/OrderController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

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
    }>;
}

const props = defineProps<Props>();
const { IconList, BusinessIconList } = useIcon();
const { openDrawer } = useCreateDrawer();
const confirm = useConfirm();

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

function handleDelete(order: Props['orders'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete order #${order.id}?`,
        icon: IconList.ExclamationTriangle,
        accept: () => {
            router.delete(destroyOrder.url(order.id));
        },
    });
}

const columns = computed(() => [
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
        bodyTemplate: (data: Props['orders'][0]) => formatEnum(data.status),
    },
    {
        field: 'order_date',
        header: 'Order Date',
        sortable: true,
        columnKey: 'order_date',
        bodyTemplate: (data: Props['orders'][0]) => formatDate(data.order_date),
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
    <AppLayout page-title="Orders">
        <PageHeader heading="Orders" :business-icon="BusinessIconList.Orders">
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('order')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiCard>
                <template #content>
                    <UiDataTable
                        :value="orders"
                        :columns="columns"
                        data-key="id"
                        striped-rows
                        show-gridlines
                    >
                        <template #actions="{ data }">
                            <UiButton
                                :icon="IconList.Settings"
                                text
                                size="small"
                                @click="router.visit(editOrder.url(data.id))"
                            />
                            <UiButton
                                :icon="IconList.Close"
                                text
                                size="small"
                                severity="danger"
                                @click="handleDelete(data, $event)"
                            />
                        </template>
                    </UiDataTable>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
