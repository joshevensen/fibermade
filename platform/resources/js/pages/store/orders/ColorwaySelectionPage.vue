<script setup lang="ts">
import CreatorPageHeader from '@/components/store/CreatorPageHeader.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
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

interface WholesaleTerms {
    discount_rate: number | null;
    minimum_order_quantity: number | null;
    minimum_order_value: number | null;
    allows_preorders: boolean;
    payment_terms: string | null;
    lead_time_days: number | null;
}

interface Props {
    creator: { id: number; name: string };
    colorways: ColorwayItem[];
    collections: CollectionItem[];
    wholesale_terms: WholesaleTerms;
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

    return [...list].sort((a, b) => a.name.localeCompare(b.name));
});

const selectedColorways = computed(() =>
    props.colorways
        .filter((cw) => selectedIds.value.has(cw.id))
        .sort((a, b) => a.name.localeCompare(b.name)),
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

function formatDiscountRate(value: number | null | undefined): string {
    if (value === null || value === undefined) return '—';
    const percent = value <= 1 ? Math.round(value * 100) : Math.round(value);
    return `${percent}%`;
}

function discountRateForCalculation(): number | null {
    const rate = props.wholesale_terms.discount_rate;
    if (rate === null) return null;
    return rate <= 1 ? rate : rate / 100;
}

function wholesalePrice(retailPrice: number | null): number | null {
    const rate = discountRateForCalculation();
    if (retailPrice === null || rate === null) {
        return null;
    }
    return retailPrice * (1 - rate);
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
        <CreatorPageHeader
            :creator="props.creator"
            :back-url="`/store/${props.creator.id}/orders`"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left panel: colorway list -->
            <div class="flex flex-col gap-4 lg:col-span-2">
                <UiCard>
                    <template #title>
                        Colorway Selection
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

                        <div v-else class="flex flex-col gap-4">
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
                                        class="shrink-0 overflow-hidden rounded bg-surface-200"
                                        :class="
                                            expandedId === cw.id
                                                ? 'h-32 w-32'
                                                : 'h-16 w-16'
                                        "
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
                                        <button
                                            v-if="expandedId !== cw.id"
                                            type="button"
                                            class="mt-1 text-sm text-primary-600 hover:underline"
                                            @click.stop="toggleExpanded(cw.id)"
                                        >
                                            Show more
                                        </button>
                                        <div
                                            v-if="expandedId === cw.id"
                                            class="mt-2 space-y-1 text-sm text-surface-600"
                                        >
                                            <div>
                                                Collection:
                                                {{
                                                    cw.collections.length > 0
                                                        ? cw.collections
                                                              .map((c) => c.name)
                                                              .join(', ')
                                                        : '—'
                                                }}
                                            </div>
                                            <div>
                                                Colors:
                                                {{
                                                    cw.colors.length > 0
                                                        ? cw.colors
                                                              .map((c) =>
                                                                  formatEnum(c),
                                                              )
                                                              .join(', ')
                                                        : '—'
                                                }}
                                            </div>
                                            <div>
                                                Technique: Variegated
                                            </div>
                                            <button
                                                type="button"
                                                class="mt-2 block text-sm text-primary-600 hover:underline"
                                                @click.stop="toggleExpanded(cw.id)"
                                            >
                                                Show less
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        class="flex shrink-0 items-center"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded border-2 transition-colors"
                                            :class="
                                                selectedIds.has(cw.id)
                                                    ? 'border-primary-500 bg-primary-500 text-white'
                                                    : 'border-surface-400 bg-surface-0'
                                            "
                                        >
                                            <span
                                                v-if="selectedIds.has(cw.id)"
                                                class="text-lg leading-none"
                                            >
                                                ✓
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-if="expandedId === cw.id && (cw.description || cw.bases.length > 0)"
                                    class="border-t border-surface-100 px-4 py-3"
                                >
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
                                                        wholesale_terms.discount_rate != null &&
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
                            </button>
                        </div>
                    </template>
                </UiCard>
            </div>

            <!-- Right panel: selection sidebar -->
            <div class="lg:sticky lg:top-4 lg:col-span-1 lg:self-start">
                <UiCard>
                    <template #title>Selected</template>

                    <template #content>
                        <div class="space-y-4 text-sm">
                            <div>
                                <div
                                    v-if="
                                        wholesale_terms.minimum_order_quantity !=
                                            null &&
                                        wholesale_terms.minimum_order_value !=
                                            null
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-surface-600"
                                        >Minimum Order</span
                                    >
                                    <span>{{
                                        wholesale_terms.minimum_order_quantity
                                    }}
                                        skeins /
                                        {{
                                            formatCurrency(
                                                wholesale_terms.minimum_order_value,
                                            )
                                        }}</span>
                                </div>
                                <div
                                    v-if="
                                        wholesale_terms.discount_rate != null
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-surface-600"
                                        >Discount Rate</span
                                    >
                                    <span>{{
                                        formatDiscountRate(
                                            wholesale_terms.discount_rate,
                                        )
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-surface-600"
                                        >Allow Preorder</span
                                    >
                                    <span>{{
                                        wholesale_terms.allows_preorders
                                            ? 'Yes'
                                            : 'No'
                                    }}</span>
                                </div>
                                <div
                                    v-if="
                                        wholesale_terms.lead_time_days != null
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-surface-600"
                                        >Lead Time</span
                                    >
                                    <span>{{
                                        wholesale_terms.lead_time_days
                                    }}
                                        days</span>
                                </div>
                                <div
                                    v-if="wholesale_terms.payment_terms"
                                    class="flex justify-between"
                                >
                                    <span class="text-surface-600"
                                        >Deposit Due</span
                                    >
                                    <span>{{
                                        wholesale_terms.payment_terms
                                    }}</span>
                                </div>
                            </div>

                            <div
                                v-if="selectedColorways.length === 0"
                                class="rounded border border-dashed border-surface-200 py-6 text-center text-sm text-surface-500"
                            >
                                Select colorways from the list
                            </div>
                            <template v-else>
                                <div class="space-y-1">
                                    <div
                                        v-for="cw in selectedColorways"
                                        :key="cw.id"
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="min-w-0 truncate font-bold text-surface-700"
                                            >{{ cw.name }}</span
                                        >
                                        <UiButton
                                            label="Remove"
                                            size="small"
                                            severity="secondary"
                                            outlined
                                            @click.stop="
                                                removeSelected(cw.id)
                                            "
                                        />
                                    </div>
                                </div>

                                    <div class="mt-2 mb-4 flex justify-between">
                                        <span class="text-surface-600"
                                            >Colorways</span
                                        >
                                        <span>{{ selectedIds.size }}</span>
                                    </div>

                                <UiButton
                                    label="Continue"
                                    :disabled="selectedIds.size === 0"
                                    class="w-full"
                                    @click="handleContinue"
                                />
                            </template>
                        </div>
                    </template>
                </UiCard>
            </div>
        </div>
    </StoreLayout>
</template>
