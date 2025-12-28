<script setup lang="ts">
import {
    destroy as destroyCollection,
    edit as editCollection,
} from '@/actions/App/Http/Controllers/CollectionController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiTag from '@/components/ui/UiTag.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

interface Collection {
    id: number;
    name: string;
    slug: string;
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
const { IconList, BusinessIconList } = useIcon();
const confirm = useConfirm();
const { openDrawer } = useCreateDrawer();

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
</script>

<template>
    <AppLayout page-title="Collections">
        <PageHeader
            heading="Collections"
            :business-icon="BusinessIconList.Collections"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('collection')"
                />
            </template>
        </PageHeader>

        <div class="mt-6 flex flex-col gap-4">
            <UiDataView
                :value="filteredAndSortedCollections"
                layout="grid"
                data-key="id"
                paginator
                :rows="20"
                :sort-field="sortField"
                :sort-order="sortOrder"
            >
                <template #header>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="text-sm text-surface-600">
                                <template
                                    v-if="
                                        filteredAndSortedCollections.length !==
                                        props.collections.length
                                    "
                                >
                                    {{ filteredAndSortedCollections.length }} of
                                    {{ props.collections.length }}
                                </template>
                                <template v-else>
                                    {{ filteredAndSortedCollections.length }}
                                </template>
                                {{
                                    filteredAndSortedCollections.length === 1
                                        ? 'collection'
                                        : 'collections'
                                }}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <UiFormFieldSelect
                                name="status-filter"
                                label="Status"
                                label-position="left"
                                :options="statusFilterOptions"
                                option-label="label"
                                option-value="value"
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
                                option-label="label"
                                option-value="value"
                                :initial-value="currentSortValue"
                                :validate-on-mount="false"
                                :validate-on-blur="false"
                                :validate-on-submit="false"
                                :validate-on-value-update="false"
                                size="small"
                                class="w-40"
                                @update:model-value="handleSortChange($event)"
                            />
                        </div>
                    </div>
                </template>

                <template #grid="{ items }">
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="collection in items"
                            :key="collection.id"
                            class="group relative cursor-pointer rounded-lg border border-surface-200 bg-surface-0 p-4 transition-all hover:border-primary-500 hover:shadow-md"
                            @click="handleCollectionClick(collection)"
                        >
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <UiTag
                                        :severity="
                                            collection.status === 'active'
                                                ? 'success'
                                                : 'secondary'
                                        "
                                        :value="formatEnum(collection.status)"
                                    />
                                    <UiButton
                                        :icon="IconList.Settings"
                                        text
                                        size="small"
                                        class="opacity-0 transition-opacity group-hover:opacity-100"
                                        @click.stop="
                                            router.visit(
                                                editCollection.url(
                                                    collection.id,
                                                ),
                                            )
                                        "
                                    />
                                </div>
                                <h3
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ collection.name }}
                                </h3>
                                <p
                                    v-if="collection.description"
                                    class="line-clamp-2 text-sm text-surface-600"
                                >
                                    {{ collection.description }}
                                </p>
                                <div
                                    class="mt-2 flex flex-col gap-1 border-t border-surface-200 pt-2"
                                >
                                    <div class="flex justify-between text-sm">
                                        <span class="text-surface-500"
                                            >Colorways:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{
                                                collection.colorways_count
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="flex min-h-[60vh] items-center justify-center">
                        <p class="text-lg text-surface-500">
                            No collections found
                        </p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
