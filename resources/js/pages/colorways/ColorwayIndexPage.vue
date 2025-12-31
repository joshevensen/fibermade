<script setup lang="ts">
import { edit as editColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiImage from '@/components/ui/UiImage.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import UiTag from '@/components/ui/UiTag.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Colorway {
    id: number;
    name: string;
    slug: string;
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
const { IconList } = useIcon();
const { openDrawer } = useCreateDrawer();

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

function getImageUrl(colorway: Colorway): string | undefined {
    return colorway.primary_image_url ?? undefined;
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
    <AppLayout page-title="Colorways">
        <UiCard>
            <template #title>
                <!-- Single row with count, filters, layout toggle, and create button -->
                <div
                    class="flex flex-wrap items-center justify-between gap-4 p-4 pb-0"
                >
                    <!-- Left side: Count -->
                    <div class="text-surface-600">
                        <template
                            v-if="
                                filteredAndSortedColorways.length !==
                                props.colorways.length
                            "
                        >
                            {{ filteredAndSortedColorways.length }} of
                            {{ props.colorways.length }}
                        </template>
                        <template v-else>
                            {{ filteredAndSortedColorways.length }}
                        </template>
                        {{
                            filteredAndSortedColorways.length === 1
                                ? 'colorway'
                                : 'colorways'
                        }}
                    </div>

                    <!-- Right side: Filters and Layout toggle -->
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
                            name="technique-filter"
                            label="Technique"
                            label-position="left"
                            :options="techniqueFilterOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="techniqueFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="techniqueFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="color-filter"
                            label="Color"
                            label-position="left"
                            :options="colorFilterOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="colorFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="colorFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="collection-filter"
                            label="Collection"
                            label-position="left"
                            :options="collectionFilterOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="collectionFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="false"
                            size="small"
                            class="w-40"
                            @update:model-value="collectionFilter = $event"
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
                        <UiSelectButton
                            v-model="layout"
                            :options="[
                                { label: 'List', value: 'list' },
                                { label: 'Grid', value: 'grid' },
                            ]"
                            option-label="label"
                            option-value="value"
                            size="small"
                        />
                    </div>
                </div>
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
                >
                    <template #list="{ items }">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="colorway in items"
                                :key="colorway.id"
                                class="flex cursor-pointer items-center gap-4 rounded-lg border border-surface-200 p-2 pr-4 transition-colors hover:bg-surface-50"
                                @click="handleColorwayClick(colorway)"
                            >
                                <div class="flex-shrink-0">
                                    <UiImage
                                        :src="getImageUrl(colorway)"
                                        :alt="colorway.name"
                                        class="h-14 w-14 overflow-hidden rounded"
                                        image-class="h-full w-full object-cover"
                                    >
                                        <template #placeholder>
                                            <span
                                                class="text-xs text-surface-400"
                                                >—</span
                                            >
                                        </template>
                                    </UiImage>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-surface-900">
                                        {{ colorway.name }}
                                    </div>
                                    <div
                                        class="mt-1 flex gap-4 text-sm text-surface-600"
                                    >
                                        <span
                                            v-if="
                                                colorway.collections &&
                                                colorway.collections.length > 0
                                            "
                                        >
                                            {{
                                                colorway.collections
                                                    .map(
                                                        (c: {
                                                            id: number;
                                                            name: string;
                                                        }) => c.name,
                                                    )
                                                    .join(', ')
                                            }}
                                        </span>
                                        <span v-if="colorway.technique">
                                            {{ formatEnum(colorway.technique) }}
                                        </span>
                                        <span
                                            v-if="
                                                colorway.colors &&
                                                colorway.colors.length > 0
                                            "
                                        >
                                            {{ formatColors(colorway.colors) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <UiTag
                                        :severity="
                                            colorway.status === 'active'
                                                ? 'success'
                                                : colorway.status === 'idea'
                                                  ? 'info'
                                                  : 'secondary'
                                        "
                                        :value="formatEnum(colorway.status)"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #grid="{ items }">
                        <div
                            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                        >
                            <div
                                v-for="colorway in items"
                                :key="colorway.id"
                                class="cursor-pointer rounded-lg border border-surface-200 bg-surface-0 p-4 transition-all hover:border-primary-500 hover:shadow-md"
                                @click="handleColorwayClick(colorway)"
                            >
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-start">
                                        <UiTag
                                            :severity="
                                                colorway.status === 'active'
                                                    ? 'success'
                                                    : colorway.status === 'idea'
                                                      ? 'info'
                                                      : 'secondary'
                                            "
                                            :value="formatEnum(colorway.status)"
                                        />
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-surface-900"
                                    >
                                        {{ colorway.name }}
                                    </h3>
                                    <UiImage
                                        :src="getImageUrl(colorway)"
                                        :alt="colorway.name"
                                        class="aspect-square w-full overflow-hidden rounded"
                                        image-class="h-full w-full object-cover"
                                    >
                                        <template #placeholder>
                                            <span
                                                class="text-2xl text-surface-400"
                                                >—</span
                                            >
                                        </template>
                                    </UiImage>
                                    <div
                                        class="mt-2 flex flex-col gap-1 border-t border-surface-200 pt-2"
                                    >
                                        <div
                                            v-if="
                                                colorway.collections &&
                                                colorway.collections.length > 0
                                            "
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-surface-500"
                                                >Collection:</span
                                            >
                                            <span
                                                class="font-medium text-surface-900"
                                                >{{
                                                    colorway.collections
                                                        .map(
                                                            (c: {
                                                                id: number;
                                                                name: string;
                                                            }) => c.name,
                                                        )
                                                        .join(', ')
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="colorway.technique"
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-surface-500"
                                                >Technique:</span
                                            >
                                            <span
                                                class="font-medium text-surface-900"
                                                >{{
                                                    formatEnum(
                                                        colorway.technique,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="
                                                colorway.colors &&
                                                colorway.colors.length > 0
                                            "
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-surface-500"
                                                >Colors:</span
                                            >
                                            <span
                                                class="font-medium text-surface-900"
                                                >{{
                                                    formatColors(
                                                        colorway.colors,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div
                            class="flex min-h-[60vh] items-center justify-center"
                        >
                            <p class="text-lg text-surface-500">
                                No colorways found
                            </p>
                        </div>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </AppLayout>
</template>
