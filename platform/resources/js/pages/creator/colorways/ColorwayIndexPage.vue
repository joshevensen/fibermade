<script setup lang="ts">
import { edit as editColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import GridItem from '@/components/GridItem.vue';
import GridItemWrapper from '@/components/GridItemWrapper.vue';
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Colorway {
    id: number;
    name: string;
    description?: string | null;
    technique?: string | null;
    colors?: string[] | null;
    status: string;
    shopify_product_id?: string | null;
    created_at: string;
    primary_image_url?: string | null;
    collections?: Array<{ id: number; name: string }>;
}

interface Props {
    colorways: Colorway[];
    colorwayStatusOptions: Array<{ label: string; value: string }>;
    techniqueOptions: Array<{ label: string; value: string }>;
    colorOptions: Array<{ label: string; value: string }>;
    collectionOptions: Array<{ label: string; value: number }>;
}

const props = defineProps<Props>();

// Layout state
const layout = ref<'list' | 'grid'>('list');

// Filter state
const statusFilter = ref<string | 'all'>('all');
const techniqueFilter = ref<string | 'all'>('all');
const colorFilter = ref<string | 'all'>('all');
const collectionFilter = ref<number | 'all' | 'none'>('all');

// Sort state - using DataView's built-in sorting
const sortField = ref<string>('name');
const sortOrder = ref<number>(1); // 1 = ascending, -1 = descending

// Add "All" option to filters
const statusFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.colorwayStatusOptions,
];

const techniqueFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.techniqueOptions,
];

const colorFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.colorOptions,
];

const collectionFilterOptions = [
    { label: 'All', value: 'all' },
    { label: 'None', value: 'none' },
    ...props.collectionOptions,
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

function formatColors(colors: string[] | null | undefined): string {
    if (!colors || colors.length === 0) {
        return '';
    }
    return colors.map((color) => formatEnum(color)).join(', ');
}

function getStatusSeverity(status: string): 'success' | 'info' | 'secondary' {
    if (status === 'active') {
        return 'success';
    }
    if (status === 'idea') {
        return 'info';
    }
    return 'secondary';
}

function getListItemProps(colorway: Colorway) {
    const metadata: string[] = [];
    if (colorway.collections && colorway.collections.length > 0) {
        metadata.push(
            colorway.collections
                .map((c: { id: number; name: string }) => c.name)
                .join(', '),
        );
    }
    if (colorway.technique) {
        metadata.push(formatEnum(colorway.technique));
    }
    if (colorway.colors && colorway.colors.length > 0) {
        metadata.push(formatColors(colorway.colors));
    }

    return {
        title: colorway.name,
        image: colorway.primary_image_url
            ? { src: colorway.primary_image_url, alt: colorway.name }
            : undefined,
        metadata: metadata.length > 0 ? metadata : undefined,
        tag: {
            severity: getStatusSeverity(colorway.status),
            value: formatEnum(colorway.status),
        },
    };
}

function getGridItemProps(colorway: Colorway) {
    const metadata: Array<{ label: string; value: string }> = [];
    if (colorway.collections && colorway.collections.length > 0) {
        metadata.push({
            label: 'Collection',
            value: colorway.collections
                .map((c: { id: number; name: string }) => c.name)
                .join(', '),
        });
    }
    if (colorway.technique) {
        metadata.push({
            label: 'Technique',
            value: formatEnum(colorway.technique),
        });
    }
    if (colorway.colors && colorway.colors.length > 0) {
        metadata.push({
            label: 'Colors',
            value: formatColors(colorway.colors),
        });
    }

    return {
        title: colorway.name,
        image: colorway.primary_image_url
            ? { src: colorway.primary_image_url, alt: colorway.name }
            : undefined,
        metadata: metadata.length > 0 ? metadata : undefined,
        tag: {
            severity: getStatusSeverity(colorway.status),
            value: formatEnum(colorway.status),
        },
    };
}

// Sort and filter colorways
const filteredAndSortedColorways = computed(() => {
    let filtered = [...props.colorways];

    // Apply status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(
            (colorway) => colorway.status === statusFilter.value,
        );
    }

    // Apply technique filter
    if (techniqueFilter.value !== 'all') {
        filtered = filtered.filter(
            (colorway) => colorway.technique === techniqueFilter.value,
        );
    }

    // Apply color filter
    if (colorFilter.value !== 'all') {
        filtered = filtered.filter((colorway) => {
            if (!colorway.colors || colorway.colors.length === 0) {
                return false;
            }
            return colorway.colors.includes(colorFilter.value);
        });
    }

    // Apply collection filter
    if (collectionFilter.value !== 'all') {
        if (collectionFilter.value === 'none') {
            filtered = filtered.filter(
                (colorway) =>
                    !colorway.collections || colorway.collections.length === 0,
            );
        } else {
            filtered = filtered.filter((colorway) => {
                if (
                    !colorway.collections ||
                    colorway.collections.length === 0
                ) {
                    return false;
                }
                return colorway.collections.some(
                    (c: { id: number; name: string }) =>
                        c.id === collectionFilter.value,
                );
            });
        }
    }

    // For created_at sorting, we need custom logic since DataView's built-in sorting
    // works best with simple field names. We'll handle it in the computed property.
    if (sortField.value === 'created_at') {
        filtered.sort((a, b) => {
            const dateA = new Date(a.created_at).getTime();
            const dateB = new Date(b.created_at).getTime();
            return sortOrder.value === 1 ? dateA - dateB : dateB - dateA;
        });
    }

    return filtered;
});

function handleColorwayClick(colorway: Colorway): void {
    router.visit(editColorway.url(colorway.id));
}

const sortOptions = [
    { label: 'Name A-Z', value: { field: 'name', order: 1 } },
    { label: 'Name Z-A', value: { field: 'name', order: -1 } },
    { label: 'Newest First', value: { field: 'created_at', order: -1 } },
    { label: 'Oldest First', value: { field: 'created_at', order: 1 } },
];

const currentSortValue = computed(() => ({
    field: sortField.value,
    order: sortOrder.value,
}));

function handleSortChange(value: { field: string; order: number }): void {
    sortField.value = value.field;
    sortOrder.value = value.order;
}
</script>

<template>
    <CreatorLayout page-title="Colorways">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.colorways.length"
                    :filtered-count="filteredAndSortedColorways.length"
                    label="colorway"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="status-filter"
                            label="Status"
                            label-position="left"
                            :options="statusFilterOptions"
                            :initial-value="statusFilter"
                            size="small"
                            class="w-40"
                            @update:model-value="statusFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="technique-filter"
                            label="Technique"
                            label-position="left"
                            :options="techniqueFilterOptions"
                            :initial-value="techniqueFilter"
                            size="small"
                            @update:model-value="techniqueFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="color-filter"
                            label="Color"
                            label-position="left"
                            :options="colorFilterOptions"
                            :initial-value="colorFilter"
                            size="small"
                            @update:model-value="colorFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="collection-filter"
                            label="Collection"
                            label-position="left"
                            :options="collectionFilterOptions"
                            :initial-value="collectionFilter"
                            size="small"
                            @update:model-value="collectionFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="sort"
                            label="Sort"
                            label-position="left"
                            :options="sortOptions"
                            :initial-value="currentSortValue"
                            size="small"
                            @update:model-value="handleSortChange($event)"
                        />
                    </template>
                    <template #toggle>
                        <UiSelectButton
                            v-model="layout"
                            :options="[
                                { label: 'List', value: 'list' },
                                { label: 'Grid', value: 'grid' },
                            ]"
                            size="small"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <UiDataView
                    :value="filteredAndSortedColorways"
                    :layout="layout"
                    data-key="id"
                    paginator
                    :rows="20"
                    :sort-field="sortField"
                    :sort-order="sortOrder"
                    empty-message="No colorways found"
                >
                    <template #list="{ items }">
                        <ListItemWrapper>
                            <ListItem
                                v-for="colorway in items"
                                :key="colorway.id"
                                v-bind="getListItemProps(colorway)"
                                @click="handleColorwayClick(colorway)"
                            />
                        </ListItemWrapper>
                    </template>

                    <template #grid="{ items }">
                        <GridItemWrapper>
                            <GridItem
                                v-for="colorway in items"
                                :key="colorway.id"
                                v-bind="getGridItemProps(colorway)"
                                @click="handleColorwayClick(colorway)"
                            />
                        </GridItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
