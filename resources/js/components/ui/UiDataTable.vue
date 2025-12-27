<script setup lang="ts">
import PrimeDataTable from 'primevue/datatable';
import PrimeColumn from 'primevue/column';

interface Column {
    field?: string | ((item: any) => string);
    header?: string;
    footer?: string;
    sortable?: boolean;
    sortField?: string | ((item: any) => string);
    filterField?: string | ((item: any) => string);
    dataType?: string;
    columnKey?: string;
    bodyTemplate?: (data: any) => string | number | null | undefined;
    [key: string]: any;
}

interface Props {
    value?: any[];
    columns?: Column[];
    dataKey?: string | number | symbol | ((item: any) => string);
    rows?: number;
    first?: number;
    totalRecords?: number;
    paginator?: boolean;
    paginatorPosition?: 'top' | 'bottom' | 'both';
    loading?: boolean;
    sortField?: string | ((item: any) => string);
    sortOrder?: number;
    selection?: any | any[];
    selectionMode?: 'single' | 'multiple';
    scrollable?: boolean;
    scrollHeight?: string;
    size?: 'small' | 'large';
    stripedRows?: boolean;
    showGridlines?: boolean;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    paginatorPosition: 'bottom',
    size: 'small',
    emptyMessage: 'No records found',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDataTable
        v-bind="$attrs"
        :value="value"
        :dataKey="dataKey"
        :rows="rows"
        :first="first"
        :totalRecords="totalRecords"
        :paginator="paginator"
        :paginatorPosition="paginatorPosition"
        :loading="loading"
        :sortField="sortField"
        :sortOrder="sortOrder"
        :selection="selection"
        :selectionMode="selectionMode"
        :scrollable="scrollable"
        :scrollHeight="scrollHeight"
        :size="size"
        :stripedRows="stripedRows"
        :showGridlines="showGridlines"
    >
        <PrimeColumn
            v-for="(col, index) of columns"
            :key="col.columnKey || (typeof col.field === 'string' ? col.field : null) || col.header || index"
            v-bind="col"
        >
            <template v-if="col.bodyTemplate" #body="{ data }">
                {{ col.bodyTemplate(data) }}
            </template>
        </PrimeColumn>
        <PrimeColumn
            v-if="$slots.actions"
            header="Actions"
            :exportable="false"
            style="width: 8rem"
            columnKey="actions"
        >
            <template #body="{ data }">
                <div class="flex gap-2">
                    <slot name="actions" :data="data" />
                </div>
            </template>
        </PrimeColumn>
        <slot />
        <template #empty>
            <div class="flex items-center justify-center min-h-[60vh]">
                <p class="text-surface-500 text-lg">{{ emptyMessage }}</p>
            </div>
        </template>
    </PrimeDataTable>
</template>

