<script setup lang="ts">
import PageFilter from '@/components/PageFilter.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiTag from '@/components/ui/UiTag.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface BaseItem {
    id: number;
    descriptor: string;
    weight: string | null;
    retail_price: number | null;
    inventory_quantity: number;
}

interface CollectionItem {
    id: number;
    name: string;
}

interface ColorwayItem {
    id: number;
    name: string;
    description: string | null;
    status: string;
    colors: string[];
    primary_image_url: string | null;
    collections: CollectionItem[];
    bases: BaseItem[];
}

interface Props {
    creator: { id: number; name: string };
    colorways: ColorwayItem[];
    collections: CollectionItem[];
    discount_rate: number | null;
}

const props = defineProps<Props>();

const selectedIds = ref<Set<number>>(new Set());
const selectedCollectionId = ref<string>('all');
const selectedColors = ref<string[]>([]);
const expandedId = ref<number | null>(null);

const collectionFilterOptions = computed(() => [
    { label: 'All collections', value: 'all' },
    ...props.collections.map((c) => ({ label: c.name, value: String(c.id) })),
]);

const allColorOptions = computed(() => {
    const set = new Set<string>();
    for (const cw of props.colorways) {
        for (const c of cw.colors) {
            set.add(c);
        }
    }
    return Array.from(set)
        .sort()
        .map((value) => ({ label: formatEnum(value), value }));
});

const filteredColorways = computed(() => {
    let list = props.colorways;

    if (selectedCollectionId.value !== 'all') {
        const id = Number(selectedCollectionId.value);
        list = list.filter((cw) => cw.collections.some((c) => c.id === id));
    }

    if (selectedColors.value.length > 0) {
        const set = new Set(selectedColors.value);
        list = list.filter((cw) => cw.colors.some((c) => set.has(c)));
    }

    return list;
});

const selectedColorways = computed(() =>
    props.colorways.filter((cw) => selectedIds.value.has(cw.id)),
);

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function wholesalePrice(retailPrice: number | null): number | null {
    if (retailPrice === null || props.discount_rate === null) {
        return null;
    }
    return retailPrice * (1 - props.discount_rate);
}

function toggleSelected(id: number): void {
    const next = new Set(selectedIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    selectedIds.value = next;
}

function removeSelected(id: number): void {
    const next = new Set(selectedIds.value);
    next.delete(id);
    selectedIds.value = next;
}

function toggleExpanded(id: number): void {
    expandedId.value = expandedId.value === id ? null : id;
}

function handleContinue(): void {
    if (selectedIds.value.size === 0) return;
    const ids = Array.from(selectedIds.value).join(',');
    router.visit(`/store/${props.creator.id}/order/review?colorways=${ids}`);
}
</script>

<template>
    <StoreLayout :page-title="`New order — ${props.creator.name}`">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left panel: colorway list -->
            <div class="flex flex-col gap-4 lg:col-span-2">
                <UiCard>
                    <template #title>
                        <PageFilter
                            :count="props.colorways.length"
                            :filtered-count="filteredColorways.length"
                            label="colorway"
                        >
                            <template #filters>
                                <UiFormFieldSelect
                                    name="collection-filter"
                                    label="Collection"
                                    label-position="left"
                                    :options="collectionFilterOptions"
                                    :initial-value="selectedCollectionId"
                                    :validate-on-mount="false"
                                    :validate-on-blur="false"
                                    :validate-on-submit="false"
                                    :validate-on-value-update="true"
                                    size="small"
                                    class="w-48"
                                    @update:model-value="
                                        selectedCollectionId = $event
                                    "
                                />
                                <UiFormFieldMultiSelect
                                    v-if="allColorOptions.length > 0"
                                    name="color-filter"
                                    label="Color"
                                    label-position="left"
                                    :options="allColorOptions"
                                    :initial-value="selectedColors"
                                    :validate-on-mount="false"
                                    :validate-on-blur="false"
                                    :validate-on-submit="false"
                                    size="small"
                                    placeholder="All colors"
                                    class="w-48"
                                    @update:model-value="
                                        selectedColors = $event
                                    "
                                />
                            </template>
                        </PageFilter>
                    </template>

                    <template #content>
                        <div
                            v-if="props.colorways.length === 0"
                            class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                        >
                            No colorways available. Ask this creator to add
                            colorways to their catalog.
                        </div>

                        <div
                            v-else-if="filteredColorways.length === 0"
                            class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                        >
                            No colorways match the current filters.
                        </div>

                        <div
                            v-else
                            class="flex max-h-[70vh] flex-col gap-4 overflow-y-auto"
                        >
                            <button
                                v-for="cw in filteredColorways"
                                :key="cw.id"
                                type="button"
                                class="rounded-lg border text-left transition-colors focus:ring-2 focus:ring-primary-500 focus:outline-none"
                                :class="
                                    selectedIds.has(cw.id)
                                        ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500'
                                        : 'border-surface-200 bg-surface-0 hover:border-surface-300'
                                "
                                @click="toggleSelected(cw.id)"
                            >
                                <div class="flex gap-4 p-4">
                                    <div
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded bg-surface-200"
                                    >
                                        <img
                                            v-if="cw.primary_image_url"
                                            :src="cw.primary_image_url"
                                            :alt="cw.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-surface-400"
                                        >
                                            —
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="font-medium text-surface-900"
                                        >
                                            {{ cw.name }}
                                        </div>
                                        <div
                                            v-if="cw.collections.length > 0"
                                            class="mt-1 text-sm text-surface-600"
                                        >
                                            {{
                                                cw.collections
                                                    .map((c) => c.name)
                                                    .join(', ')
                                            }}
                                        </div>
                                        <div
                                            v-if="cw.colors.length > 0"
                                            class="mt-2 flex flex-wrap gap-1"
                                        >
                                            <UiTag
                                                v-for="color in cw.colors"
                                                :key="color"
                                                severity="secondary"
                                                :value="formatEnum(color)"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        class="flex shrink-0 items-center text-primary-600"
                                    >
                                        <span
                                            v-if="selectedIds.has(cw.id)"
                                            class="text-lg"
                                            aria-hidden="true"
                                        >
                                            ✓
                                        </span>
                                    </div>
                                </div>

                                <!-- Expandable: description + bases -->
                                <div
                                    v-if="expandedId === cw.id"
                                    class="border-t border-surface-100 px-4 py-3"
                                >
                                    <button
                                        type="button"
                                        class="mb-2 text-sm text-primary-600 hover:underline"
                                        @click.stop="toggleExpanded(cw.id)"
                                    >
                                        Show less
                                    </button>
                                    <p
                                        v-if="cw.description"
                                        class="mb-3 text-sm text-surface-600"
                                    >
                                        {{ cw.description }}
                                    </p>
                                    <div
                                        v-if="cw.bases.length > 0"
                                        class="space-y-2 text-sm"
                                    >
                                        <div
                                            v-for="base in cw.bases"
                                            :key="base.id"
                                            class="flex flex-wrap items-baseline justify-between gap-2"
                                        >
                                            <span class="text-surface-700">
                                                {{ base.descriptor }}
                                                <span
                                                    v-if="base.weight"
                                                    class="text-surface-500"
                                                >
                                                    ({{
                                                        formatEnum(base.weight)
                                                    }})
                                                </span>
                                                —
                                                {{ base.inventory_quantity }}
                                                in stock
                                            </span>
                                            <span>
                                                <template
                                                    v-if="
                                                        discount_rate != null &&
                                                        base.retail_price !=
                                                            null
                                                    "
                                                >
                                                    <span
                                                        class="font-medium text-primary-600"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                wholesalePrice(
                                                                    base.retail_price,
                                                                ),
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        class="ml-1 text-surface-500 line-through"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                base.retail_price,
                                                            )
                                                        }}
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    <span
                                                        class="font-medium text-surface-700"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                base.retail_price,
                                                            )
                                                        }}
                                                    </span>
                                                </template>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="border-t border-surface-100 px-4 py-2"
                                >
                                    <button
                                        type="button"
                                        class="text-sm text-primary-600 hover:underline"
                                        @click.stop="toggleExpanded(cw.id)"
                                    >
                                        Show more
                                    </button>
                                </div>
                            </button>
                        </div>
                    </template>
                </UiCard>
            </div>

            <!-- Right panel: selection sidebar -->
            <div class="lg:sticky lg:top-4 lg:col-span-1 lg:self-start">
                <UiCard>
                    <template #title>
                        Selected ({{ selectedIds.size }})
                    </template>

                    <template #content>
                        <div
                            v-if="selectedColorways.length === 0"
                            class="rounded border border-dashed border-surface-200 py-6 text-center text-sm text-surface-500"
                        >
                            Select colorways from the list
                        </div>
                        <ul v-else class="flex flex-col gap-2">
                            <li
                                v-for="cw in selectedColorways"
                                :key="cw.id"
                                class="flex items-center justify-between gap-2 rounded border border-surface-200 bg-surface-50 px-3 py-2"
                            >
                                <span class="min-w-0 truncate text-sm">{{
                                    cw.name
                                }}</span>
                                <UiButton
                                    label="Remove"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click.stop="removeSelected(cw.id)"
                                />
                            </li>
                        </ul>
                        <div class="mt-4">
                            <UiButton
                                label="Continue"
                                :disabled="selectedIds.size === 0"
                                class="w-full"
                                @click="handleContinue"
                            />
                        </div>
                    </template>
                </UiCard>
            </div>
        </div>
    </StoreLayout>
</template>
