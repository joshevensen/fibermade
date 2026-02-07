<script lang="ts">
import type { DataTableTokenSections } from '@primeuix/themes/types/datatable';

export const dataTableRoot: DataTableTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const dataTableHeader: DataTableTokenSections.Header = {
    background: '{content.background}',
    borderColor: '{datatable.border.color}',
    color: '{content.color}',
    borderWidth: '0 0 1px 0',
    padding: '0.75rem 1rem',
    sm: {
        padding: '0.375rem 0.5rem'
    },
    lg: {
        padding: '1rem 1.25rem'
    }
};

export const dataTableHeaderCell: DataTableTokenSections.HeaderCell = {
    background: '{content.background}',
    hoverBackground: '{content.hover.background}',
    selectedBackground: '{highlight.background}',
    borderColor: '{datatable.border.color}',
    color: '{content.color}',
    hoverColor: '{content.hover.color}',
    selectedColor: '{highlight.color}',
    gap: '0.5rem',
    padding: '0.75rem 1rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '-1px',
        shadow: '{focus.ring.shadow}'
    },
    sm: {
        padding: '0.375rem 0.5rem'
    },
    lg: {
        padding: '1rem 1.25rem'
    }
};

export const dataTableColumnTitle: DataTableTokenSections.ColumnTitle = {
    fontWeight: '600'
};

export const dataTableRow: DataTableTokenSections.Row = {
    background: '{content.background}',
    hoverBackground: '{content.hover.background}',
    selectedBackground: '{highlight.background}',
    color: '{content.color}',
    hoverColor: '{content.hover.color}',
    selectedColor: '{highlight.color}',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '-1px',
        shadow: '{focus.ring.shadow}'
    }
};

export const dataTableBodyCell: DataTableTokenSections.BodyCell = {
    borderColor: '{datatable.border.color}',
    padding: '0.75rem 1rem',
    sm: {
        padding: '0.375rem 0.5rem'
    },
    lg: {
        padding: '1rem 1.25rem'
    }
};

export const dataTableFooterCell: DataTableTokenSections.FooterCell = {
    background: '{content.background}',
    borderColor: '{datatable.border.color}',
    color: '{content.color}',
    padding: '0.75rem 1rem',
    sm: {
        padding: '0.375rem 0.5rem'
    },
    lg: {
        padding: '1rem 1.25rem'
    }
};

export const dataTableColumnFooter: DataTableTokenSections.ColumnFooter = {
    fontWeight: '600'
};

export const dataTableFooter: DataTableTokenSections.Footer = {
    background: '{content.background}',
    borderColor: '{datatable.border.color}',
    color: '{content.color}',
    borderWidth: '0 0 1px 0',
    padding: '0.75rem 1rem',
    sm: {
        padding: '0.375rem 0.5rem'
    },
    lg: {
        padding: '1rem 1.25rem'
    }
};

export const dataTableDropPoint: DataTableTokenSections.DropPoint = {
    color: '{primary.color}'
};

export const dataTableColumnResizer: DataTableTokenSections.ColumnResizer = {
    width: '0.5rem'
};

export const dataTableResizeIndicator: DataTableTokenSections.ResizeIndicator = {
    width: '1px',
    color: '{primary.color}'
};

export const dataTableSortIcon: DataTableTokenSections.SortIcon = {
    color: '{text.muted.color}',
    hoverColor: '{text.hover.muted.color}',
    size: '0.875rem'
};

export const dataTableLoadingIcon: DataTableTokenSections.LoadingIcon = {
    size: '2rem'
};

export const dataTableRowToggleButton: DataTableTokenSections.RowToggleButton = {
    hoverBackground: '{content.hover.background}',
    selectedHoverBackground: '{content.background}',
    color: '{text.muted.color}',
    hoverColor: '{text.color}',
    selectedHoverColor: '{primary.color}',
    size: '1.75rem',
    borderRadius: '50%',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};

export const dataTableFilter: DataTableTokenSections.Filter = {
    inlineGap: '0.5rem',
    overlaySelect: {
        background: '{overlay.select.background}',
        borderColor: '{overlay.select.border.color}',
        borderRadius: '{overlay.select.border.radius}',
        color: '{overlay.select.color}',
        shadow: '{overlay.select.shadow}'
    },
    overlayPopover: {
        background: '{overlay.popover.background}',
        borderColor: '{overlay.popover.border.color}',
        borderRadius: '{overlay.popover.border.radius}',
        color: '{overlay.popover.color}',
        shadow: '{overlay.popover.shadow}',
        padding: '{overlay.popover.padding}',
        gap: '0.5rem'
    },
    rule: {
        borderColor: '{content.border.color}'
    },
    constraintList: {
        padding: '{list.padding}',
        gap: '{list.gap}'
    },
    constraint: {
        focusBackground: '{list.option.focus.background}',
        selectedBackground: '{list.option.selected.background}',
        selectedFocusBackground: '{list.option.selected.focus.background}',
        color: '{list.option.color}',
        focusColor: '{list.option.focus.color}',
        selectedColor: '{list.option.selected.color}',
        selectedFocusColor: '{list.option.selected.focus.color}',
        separator: {
            borderColor: '{content.border.color}'
        },
        padding: '{list.option.padding}',
        borderRadius: '{list.option.border.radius}'
    }
};

export const dataTablePaginatorTop: DataTableTokenSections.PaginatorTop = {
    borderColor: '{datatable.border.color}',
    borderWidth: '0'
};

export const dataTablePaginatorBottom: DataTableTokenSections.PaginatorBottom = {
    borderColor: '{datatable.border.color}',
    borderWidth: '0'
};

export const dataTableLight = {
    root: {
        borderColor: '{content.border.color}'
    },
    row: {
        stripedBackground: '{surface.50}'
    },
    bodyCell: {
        selectedBorderColor: '{primary.100}'
    }
};

export const dataTableDark = {
    root: {
        borderColor: '{surface.800}'
    },
    row: {
        stripedBackground: '{surface.950}'
    },
    bodyCell: {
        selectedBorderColor: '{primary.900}'
    }
};

export const dataTableCss: DataTableTokenSections.CSS = /*css*/ `
    .p-datatable-mask.p-overlay-mask {
        --px-mask-background: light-dark(rgba(255,255,255,0.5),rgba(0,0,0,0.3));
    }
`;
</script>

<script setup lang="ts">
import { computed } from 'vue';
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

const shouldShowPaginator = computed(() => {
    if (!props.paginator) {
        return false;
    }
    if (props.totalRecords !== undefined) {
        return props.totalRecords > (props.rows ?? 0);
    }
    const dataLength = props.value?.length ?? 0;
    const rowsValue = props.rows ?? 0;
    return dataLength > rowsValue;
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
        :paginator="shouldShowPaginator"
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
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <PrimeColumn
            v-for="(col, index) of columns"
            :key="col.columnKey || (typeof col.field === 'string' ? col.field : null) || col.header || index"
            v-bind="col"
        >
            <template v-if="col.bodyTemplate && !$slots[col.columnKey || '']" #body="{ data }">
                {{ col.bodyTemplate(data) }}
            </template>
            <template v-else-if="$slots[col.columnKey || '']" #body="{ data }">
                <slot :name="col.columnKey || ''" :data="data" />
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
            <div class="flex min-h-[60vh] items-center justify-center">
                <p class="text-lg text-surface-500">{{ emptyMessage }}</p>
            </div>
        </template>
    </PrimeDataTable>
</template>

