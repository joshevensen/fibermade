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
}

const props = withDefaults(defineProps<Props>(), {
    paginatorPosition: 'bottom',
    size: 'small',
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
        />
        <slot />
    </PrimeDataTable>
</template>

