<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import { edit as editDiscount, destroy as destroyDiscount } from '@/actions/App/Http/Controllers/DiscountController';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    discounts: Array<{
        id: number;
        name: string;
        type: string;
        code: string;
        parameters?: Record<string, any> | null;
        starts_at?: string | null;
        ends_at?: string | null;
        is_active: boolean;
        shopify_discount_id?: string | null;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const { openDrawer } = useCreateDrawer();
const confirm = useConfirm();

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
}

function formatBoolean(value: boolean | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return value ? 'Yes' : 'No';
}

function formatParameters(parameters: Record<string, any> | null | undefined): string {
    if (!parameters || Object.keys(parameters).length === 0) {
        return '';
    }
    return JSON.stringify(parameters);
}

function handleDelete(discount: Props['discounts'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${discount.name}?`,
        icon: IconList.ExclamationTriangle,
        accept: () => {
            router.delete(destroyDiscount.url(discount.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'code', header: 'Code', sortable: true, columnKey: 'code' },
    {
        field: 'type',
        header: 'Type',
        sortable: true,
        columnKey: 'type',
        bodyTemplate: (data: Props['discounts'][0]) => formatEnum(data.type),
    },
    {
        field: 'is_active',
        header: 'Is Active',
        sortable: true,
        columnKey: 'is_active',
        bodyTemplate: (data: Props['discounts'][0]) => formatBoolean(data.is_active),
    },
    {
        field: 'starts_at',
        header: 'Starts At',
        sortable: true,
        columnKey: 'starts_at',
        bodyTemplate: (data: Props['discounts'][0]) => formatDate(data.starts_at),
    },
    {
        field: 'ends_at',
        header: 'Ends At',
        sortable: true,
        columnKey: 'ends_at',
        bodyTemplate: (data: Props['discounts'][0]) => formatDate(data.ends_at),
    },
]);
</script>

<template>
    <AppLayout page-title="Discounts">
        <PageHeader
            heading="Discounts"
            :icon="IconList.Discounts"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('discount')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiCard>
                <template #content>
                    <UiDataTable
                        :value="discounts"
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
                                @click="router.visit(editDiscount.url(data.id))"
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
