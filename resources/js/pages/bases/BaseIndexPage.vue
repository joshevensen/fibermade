<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import { create as createBase, edit as editBase, destroy as destroyBase } from '@/actions/App/Http/Controllers/BaseController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    bases: Array<{
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        status: string;
        weight?: string | null;
        descriptor?: string | null;
        size?: number | null;
        cost?: number | null;
        retail_price?: number | null;
        wool_percent?: number | null;
        nylon_percent?: number | null;
        alpaca_percent?: number | null;
        yak_percent?: number | null;
        camel_percent?: number | null;
        cotton_percent?: number | null;
        bamboo_percent?: number | null;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const confirm = useConfirm();

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

function formatPercent(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return `${value}%`;
}

function handleDelete(base: Props['bases'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${base.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyBase.url(base.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'descriptor', header: 'Descriptor', sortable: true, columnKey: 'descriptor' },
    { field: 'size', header: 'Size', sortable: true, columnKey: 'size' },
    {
        field: 'status',
        header: 'Status',
        sortable: true,
        columnKey: 'status',
        bodyTemplate: (data: Props['bases'][0]) => formatEnum(data.status),
    },
    {
        field: 'weight',
        header: 'Weight',
        sortable: true,
        columnKey: 'weight',
        bodyTemplate: (data: Props['bases'][0]) => formatEnum(data.weight),
    },
    {
        field: 'retail_price',
        header: 'Retail Price',
        sortable: true,
        columnKey: 'retail_price',
        bodyTemplate: (data: Props['bases'][0]) => formatCurrency(data.retail_price),
    },
]);
</script>

<template>
    <AppLayout page-title="Bases">
        <PageHeader
            heading="Bases"
            :icon="IconList.Bases"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Base"
                    @click="router.visit(createBase.url())"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataTable
                :value="bases"
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
                        @click="router.visit(editBase.url(data.id))"
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
        </div>
    </AppLayout>
</template>
