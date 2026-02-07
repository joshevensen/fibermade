<script lang="ts">
import type { DataViewTokenSections } from '@primeuix/themes/types/dataview';

export const dataViewRoot: DataViewTokenSections.Root = {
    borderColor: 'transparent',
    borderWidth: '0',
    borderRadius: '0',
    padding: '0'
};

export const dataViewHeader: DataViewTokenSections.Header = {
    background: '{content.background}',
    color: '{content.color}',
    borderColor: '{content.border.color}',
    borderWidth: '0',
    padding: '0.75rem 1rem',
    borderRadius: '0'
};

export const dataViewContent: DataViewTokenSections.Content = {
    background: '{content.background}',
    color: '{content.color}',
    borderColor: 'transparent',
    borderWidth: '0',
    padding: '0',
    borderRadius: '0'
};

export const dataViewFooter: DataViewTokenSections.Footer = {
    background: '{content.background}',
    color: '{content.color}',
    borderColor: '{content.border.color}',
    borderWidth: '0',
    padding: '0.75rem 1rem',
    borderRadius: '0'
};

export const dataViewPaginatorTop: DataViewTokenSections.PaginatorTop = {
    borderColor: '{content.border.color}',
    borderWidth: '0'
};

export const dataViewPaginatorBottom: DataViewTokenSections.PaginatorBottom = {
    borderColor: '{content.border.color}',
    borderWidth: '0'
};
</script>

<script setup lang="ts">
import { computed } from 'vue';
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
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    layout: 'list',
    paginatorPosition: 'bottom',
    emptyMessage: 'No records found',
});

defineOptions({
    inheritAttrs: false,
});

const shouldShowPaginator = computed(() => {
    if (!props.paginator) {
        return false;
    }
    if (props.lazy && props.totalRecords !== undefined) {
        return props.totalRecords > (props.rows ?? 0);
    }
    const dataLength = props.value?.length ?? 0;
    const rowsValue = props.rows ?? 0;
    return dataLength > rowsValue;
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
        :paginator="shouldShowPaginator"
        :paginatorPosition="paginatorPosition"
        :lazy="lazy"
        :sortField="sortField"
        :sortOrder="sortOrder"
        :dataKey="dataKey"
    >
            <template v-if="$slots.header" #header>
                <slot name="header" />
            </template>
            <template #empty="slotProps">
                <slot name="empty" v-bind="slotProps">
                    <div
                        class="flex min-h-[60vh] items-center justify-center"
                    >
                        <p class="text-lg text-surface-500">
                            {{ props.emptyMessage }}
                        </p>
                    </div>
                </slot>
            </template>
            <template #list="slotProps">
                <slot name="list" v-bind="slotProps" />
            </template>
            <template #grid="slotProps">
                <slot name="grid" v-bind="slotProps" />
            </template>
        </PrimeDataView>
</template>

