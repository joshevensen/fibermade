<script setup lang="ts">
import {
    destroy as destroyCollection,
    edit as editCollection,
} from '@/actions/App/Http/Controllers/CollectionController';
import GridItem from '@/components/GridItem.vue';
import GridItemWrapper from '@/components/GridItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import { useIcon } from '@/composables/useIcon';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

interface Collection {
    id: number;
    name: string;
    description?: string | null;
    status: string;
    created_at: string;
    colorways_count: number;
}

interface Props {
    collections: Collection[];
    statusOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const confirm = useConfirm();

// Filter state
const statusFilter = ref<string | 'all'>('active');

// Sort state
const sortField = ref<string>('name');
const sortOrder = ref<number>(1); // 1 = ascending, -1 = descending

// Add "All" option to status filter
const statusFilterOptions = [
    { label: 'All', value: 'all' },
    ...props.statusOptions,
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

// Sort and filter collections
const filteredAndSortedCollections = computed(() => {
    let filtered = [...props.collections];

    // Apply status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(
            (collection) => collection.status === statusFilter.value,
        );
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

function handleCollectionClick(collection: Collection): void {
    router.visit(editCollection.url(collection.id));
}

function handleDelete(collection: Collection, event: Event): void {
    event.stopPropagation();
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${collection.name}?`,
        icon: IconList.ExclamationTriangle,
        accept: () => {
            router.delete(destroyCollection.url(collection.id));
        },
    });
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

function getGridItemProps(collection: Collection) {
    return {
        title: collection.name,
        description: collection.description ?? undefined,
        tag: {
            severity: (collection.status === 'active'
                ? 'success'
                : 'secondary') as 'success' | 'secondary',
            value: formatEnum(collection.status),
        },
        metadata: [
            {
                label: 'Colorways',
                value: collection.colorways_count.toString(),
            },
        ],
    };
}
</script>

<template>
    <CreatorLayout page-title="Collections">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.collections.length"
                    :filtered-count="filteredAndSortedCollections.length"
                    label="collection"
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
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="statusFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="sort"
                            label="Sort"
                            label-position="left"
                            :options="sortOptions"
                            :initial-value="currentSortValue"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="handleSortChange($event)"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <UiDataView
                    :value="filteredAndSortedCollections"
                    layout="grid"
                    data-key="id"
                    paginator
                    :rows="20"
                    :sort-field="sortField"
                    :sort-order="sortOrder"
                    empty-message="No collections found"
                >
                    <template #grid="{ items }">
                        <GridItemWrapper>
                            <GridItem
                                v-for="collection in items"
                                :key="collection.id"
                                v-bind="getGridItemProps(collection)"
                                @click="handleCollectionClick(collection)"
                            />
                        </GridItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
