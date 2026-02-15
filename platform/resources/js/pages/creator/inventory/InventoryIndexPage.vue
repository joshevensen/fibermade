<script setup lang="ts">
import PageFilter from '@/components/PageFilter.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import InventoryQuantityInput from './components/InventoryQuantityInput.vue';

interface Base {
    id: number;
    code: string;
    descriptor: string;
    quantity: number;
    inventory_id: number | null;
}

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
    bases: Base[];
    total_quantity: number;
}

interface Props {
    colorways: Colorway[];
    colorwayStatusOptions: Array<{ label: string; value: string }>;
    techniqueOptions: Array<{ label: string; value: string }>;
    colorOptions: Array<{ label: string; value: string }>;
    collectionOptions: Array<{ label: string; value: number }>;
}

const props = defineProps<Props>();

// Filter state
const statusFilter = ref<string | 'all'>('all');
const techniqueFilter = ref<string | 'all'>('all');
const colorFilter = ref<string | 'all'>('all');
const collectionFilter = ref<number | 'all' | 'none'>('all');

// Sort state - using DataView's built-in sorting
const sortField = ref<string>('name');
const sortOrder = ref<number>(1); // 1 = ascending, -1 = descending

// Track local quantities for total calculation
const localQuantities = reactive<Record<string, number>>({});

// Initialize local quantities from props
props.colorways.forEach((colorway) => {
    colorway.bases.forEach((base) => {
        const key = `${colorway.id}-${base.id}`;
        localQuantities[key] = base.quantity;
    });
});

// Watch for prop changes to sync local quantities
watch(
    () => props.colorways,
    (newColorways) => {
        newColorways.forEach((colorway) => {
            colorway.bases.forEach((base) => {
                const key = `${colorway.id}-${base.id}`;
                localQuantities[key] = base.quantity;
            });
        });
    },
    { deep: true },
);

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

function getTotalQuantity(colorway: Colorway): number {
    return colorway.bases.reduce((total, base) => {
        const key = `${colorway.id}-${base.id}`;
        return total + (localQuantities[key] ?? base.quantity);
    }, 0);
}

function handleQuantityChange(
    colorwayId: number,
    baseId: number,
    newQuantity: number,
): void {
    const key = `${colorwayId}-${baseId}`;
    localQuantities[key] = newQuantity;
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

// Push to Shopify
const page = usePage();
const pushToShopifyLoading = ref(false);
const successMessage = computed(
    () => (page.props.flash as { success?: string } | undefined)?.success || '',
);
const errorMessage = computed(
    () => (page.props.flash as { error?: string } | undefined)?.error || '',
);

const totalVariantCount = computed(() =>
    props.colorways.reduce((sum, c) => sum + c.bases.length, 0),
);
const showPushConfirmDialog = ref(false);

function performPushToShopify(): void {
    if (pushToShopifyLoading.value) return;
    showPushConfirmDialog.value = false;
    pushToShopifyLoading.value = true;
    router.post(
        '/creator/inventory/push-to-shopify',
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                pushToShopifyLoading.value = false;
            },
        },
    );
}

function pushToShopify(): void {
    if (pushToShopifyLoading.value) return;
    if (totalVariantCount.value >= 50) {
        showPushConfirmDialog.value = true;
    } else {
        performPushToShopify();
    }
}
</script>

<template>
    <CreatorLayout page-title="Inventory">
        <div class="mb-4 flex flex-col gap-2">
            <div class="flex items-center justify-between gap-4">
                <UiButton
                    label="Push to Shopify"
                    icon="pi pi-cloud-upload"
                    :loading="pushToShopifyLoading"
                    :disabled="pushToShopifyLoading"
                    @click="pushToShopify"
                />
            </div>
            <UiMessage v-if="successMessage" severity="success" size="small">
                {{ successMessage }}
            </UiMessage>
            <UiMessage v-if="errorMessage" severity="error" size="small">
                {{ errorMessage }}
            </UiMessage>
            <UiDialog
                v-model:visible="showPushConfirmDialog"
                header="Confirm Push to Shopify"
                modal
                size="small"
            >
                <p class="text-surface-700">
                    You are about to push
                    <strong>{{ totalVariantCount }}</strong> variants to Shopify.
                    This may take a moment. Continue?
                </p>
                <template #footer>
                    <UiButton
                        label="Cancel"
                        severity="secondary"
                        outlined
                        @click="showPushConfirmDialog = false"
                    />
                    <UiButton
                        label="Push to Shopify"
                        icon="pi pi-cloud-upload"
                        :loading="pushToShopifyLoading"
                        @click="performPushToShopify"
                    />
                </template>
            </UiDialog>
        </div>
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
                    :value="filteredAndSortedColorways"
                    layout="list"
                    data-key="id"
                    paginator
                    :rows="20"
                    :sort-field="sortField"
                    :sort-order="sortOrder"
                    empty-message="No colorways found"
                >
                    <template #list="{ items }">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="colorway in items"
                                :key="colorway.id"
                                class="flex flex-col gap-2 rounded-lg border border-surface-200 p-2 pr-4 transition-colors hover:bg-surface-50"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="font-semibold text-surface-900">
                                        {{ colorway.name }}
                                        <span class="text-surface-500"
                                            >({{
                                                getTotalQuantity(colorway)
                                            }})</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="scrollbar-thin flex gap-4 overflow-x-auto pb-2"
                                    @click.stop
                                >
                                    <InventoryQuantityInput
                                        v-for="base in colorway.bases"
                                        :key="base.id"
                                        :colorway-id="colorway.id"
                                        :base-id="base.id"
                                        :base-name="base.descriptor"
                                        :initial-quantity="base.quantity"
                                        @quantity-changed="
                                            handleQuantityChange(
                                                colorway.id,
                                                base.id,
                                                $event,
                                            )
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
