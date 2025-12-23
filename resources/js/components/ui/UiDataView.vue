<script setup lang="ts">
import PrimeDataView from 'primevue/dataview';

interface Props {
    value?: any[];
    layout?: 'list' | 'grid';
    rows?: number;
    first?: number;
    totalRecords?: number;
    paginator?: boolean;
    paginatorPosition?: 'top' | 'bottom' | 'both';
    lazy?: boolean;
    sortField?: string | ((item: any) => string);
    sortOrder?: number;
    dataKey?: string;
}

const props = withDefaults(defineProps<Props>(), {
    layout: 'list',
    paginatorPosition: 'bottom',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDataView
        v-bind="$attrs"
        :value="value"
        :layout="layout"
        :rows="rows"
        :first="first"
        :totalRecords="totalRecords"
        :paginator="paginator"
        :paginatorPosition="paginatorPosition"
        :lazy="lazy"
        :sortField="sortField"
        :sortOrder="sortOrder"
        :dataKey="dataKey"
    >
        <template #empty="slotProps">
            <slot name="empty" v-bind="slotProps" />
        </template>
        <template #list="slotProps">
            <slot name="list" v-bind="slotProps" />
        </template>
        <template #grid="slotProps">
            <slot name="grid" v-bind="slotProps" />
        </template>
    </PrimeDataView>
</template>

