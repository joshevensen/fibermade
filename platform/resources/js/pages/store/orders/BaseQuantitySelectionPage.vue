<script setup lang="ts">
import {
    saveOrder,
    submitOrder,
} from '@/actions/App/Http/Controllers/StoreController';
import CreatorPageHeader from '@/components/store/CreatorPageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface BaseItem {
    id: number;
    descriptor: string;
    weight: string | null;
    retail_price: number;
    wholesale_price: number;
    inventory_quantity: number;
}

interface ColorwayItem {
    id: number;
    name: string;
    per_pan: number;
    primary_image_url: string | null;
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

interface DraftItem {
    colorway_id: number;
    base_id: number;
    quantity: number;
}

interface Draft {
    order_id: number;
    notes?: string;
    items: DraftItem[];
}

interface Props {
    creator: { id: number; name: string };
    colorways: ColorwayItem[];
    wholesale_terms: WholesaleTerms;
    draft: Draft | null;
}

const props = defineProps<Props>();

const quantities = ref<Record<string, number>>({});
const notes = ref('');
const orderId = ref<number | null>(null);
const visibleColorwayIds = ref<number[]>([]);

function getQuantityKey(colorwayId: number, baseId: number): string {
    return `${colorwayId}-${baseId}`;
}

function formatEnum(value: string | null | undefined): string {
    if (!value) return '';
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) return '';
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

const visibleColorways = computed(() =>
    props.colorways.filter((cw) => visibleColorwayIds.value.includes(cw.id)),
);

const skeinCount = computed(() => {
    return Object.values(quantities.value).reduce((sum, qty) => sum + qty, 0);
});

function skeinCountForColorway(cw: ColorwayItem): number {
    return cw.bases.reduce(
        (sum, base) =>
            sum + (quantities.value[getQuantityKey(cw.id, base.id)] ?? 0),
        0,
    );
}

function lineTotal(colorwayId: number, baseId: number): number {
    const base = props.colorways
        .flatMap((cw) => cw.bases)
        .find((b) => b.id === baseId);
    const qty = quantities.value[getQuantityKey(colorwayId, baseId)] ?? 0;
    return (base?.wholesale_price ?? 0) * qty;
}

const subtotal = computed(() => {
    let sum = 0;
    for (const cw of visibleColorways.value) {
        for (const base of cw.bases) {
            sum += lineTotal(cw.id, base.id);
        }
    }
    return sum;
});

const total = computed(() => subtotal.value);

const minimumsAreMet = computed(() => {
    const terms = props.wholesale_terms;
    const minQty = terms.minimum_order_quantity;
    const minVal = terms.minimum_order_value;
    if (minQty != null && skeinCount.value < minQty) return false;
    if (minVal != null && subtotal.value < minVal) return false;
    return true;
});

function buildItemsPayload(): Array<{
    colorway_id: number;
    base_id: number;
    quantity: number;
}> {
    const items: Array<{
        colorway_id: number;
        base_id: number;
        quantity: number;
    }> = [];
    for (const cw of visibleColorways.value) {
        for (const base of cw.bases) {
            const qty = quantities.value[getQuantityKey(cw.id, base.id)] ?? 0;
            if (qty > 0) {
                items.push({
                    colorway_id: cw.id,
                    base_id: base.id,
                    quantity: qty,
                });
            }
        }
    }
    return items;
}

function removeColorway(colorwayId: number): void {
    visibleColorwayIds.value = visibleColorwayIds.value.filter(
        (id) => id !== colorwayId,
    );
    const cw = props.colorways.find((c) => c.id === colorwayId);
    if (cw) {
        for (const base of cw.bases) {
            delete quantities.value[getQuantityKey(colorwayId, base.id)];
        }
    }
}

function goBack(): void {
    const ids = visibleColorwayIds.value.join(',');
    router.visit(`/store/${props.creator.id}/order?colorways=${ids}`);
}

function isInputDisabled(base: BaseItem): boolean {
    console.log('base.inventory_quantity', base.inventory_quantity);

    if (base.inventory_quantity === 0) {
        console.log('isInputDisabled', props.wholesale_terms.allows_preorders);
        return !props.wholesale_terms.allows_preorders;
    }
    console.log('isInputDisabled: false');
    return false;
}

function initFromDraft(): void {
    if (props.draft) {
        orderId.value = props.draft.order_id;
        notes.value = props.draft.notes ?? '';
        for (const item of props.draft.items) {
            quantities.value[getQuantityKey(item.colorway_id, item.base_id)] =
                item.quantity;
        }
    } else {
        orderId.value = null;
        notes.value = '';
    }
}

onMounted(() => {
    visibleColorwayIds.value = props.colorways.map((c) => c.id);
    initFromDraft();
});

watch(
    () => props.draft,
    () => initFromDraft(),
    { immediate: false },
);

const saveInitialValues = computed(() => ({
    order_id: orderId.value ?? undefined,
    notes: notes.value,
}));

const { form: saveForm, onSubmit: onSaveSubmit } = useFormSubmission({
    route: () => saveOrder.post(props.creator.id),
    initialValues: { order_id: undefined as number | undefined, notes: '' },
    successMessage: 'Order saved as draft',
    transform: (values) => ({
        order_id: orderId.value ?? undefined,
        notes: values.notes,
        items: buildItemsPayload(),
    }),
});

watch(
    [orderId, notes],
    () => {
        saveForm.order_id = orderId.value ?? undefined;
        saveForm.notes = notes.value;
    },
    { immediate: true },
);

function handleSaveSubmit(event: {
    valid: boolean;
    values: Record<string, unknown>;
}): void {
    onSaveSubmit(event);
}

function handleSubmit(): void {
    if (!orderId.value || !minimumsAreMet.value) return;
    router.post(submitOrder.post(props.creator.id).url, {
        order_id: orderId.value,
    });
}

const page = usePage();
const pageErrors = computed(
    () =>
        (page.props as { errors?: Record<string, string | string[]> }).errors ??
        {},
);

function normalizeError(v: string | string[] | undefined): string | undefined {
    if (v == null) return undefined;
    return Array.isArray(v) ? v[0] : v;
}

const minQtyError = computed(() =>
    normalizeError(pageErrors.value.minimum_order_quantity),
);
const minValError = computed(() =>
    normalizeError(pageErrors.value.minimum_order_value),
);

const hasVisibleColorways = computed(() => visibleColorwayIds.value.length > 0);
</script>

<template>
    <StoreLayout :page-title="`Order — ${props.creator.name}`">
        <CreatorPageHeader
            :creator="props.creator"
            :back-url="`/store/${props.creator.id}/order`"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left panel: base quantity selection -->
            <div class="flex flex-col gap-4 lg:col-span-2">
                <UiCard>
                    <template #title> Base &amp; Quantity Selection </template>

                    <template #content>
                        <div v-if="!hasVisibleColorways" class="space-y-4">
                            <div
                                class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                            >
                                No colorways selected. Go back to select
                                colorways.
                            </div>
                            <UiButton
                                label="Back"
                                severity="secondary"
                                @click="goBack"
                            />
                        </div>

                        <template v-else>
                            <div class="flex flex-col gap-4">
                                <div
                                    v-for="cw in visibleColorways"
                                    :key="cw.id"
                                    class="flex flex-col gap-2 rounded-lg border border-surface-200 p-4 transition-colors hover:bg-surface-50"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div
                                            class="font-semibold text-surface-900"
                                        >
                                            {{ cw.name }}
                                            <span class="text-surface-500">
                                                ({{
                                                    skeinCountForColorway(cw)
                                                }})
                                            </span>
                                            <span
                                                v-if="cw.per_pan > 0"
                                                class="ml-2 font-normal text-surface-500"
                                            >
                                                {{ cw.per_pan }} skeins per pan
                                            </span>
                                        </div>
                                        <UiButton
                                            label="Remove"
                                            size="small"
                                            severity="secondary"
                                            outlined
                                            @click="removeColorway(cw.id)"
                                        />
                                    </div>
                                    <div
                                        class="scrollbar-thin flex gap-4 overflow-x-auto pb-2"
                                        @click.stop
                                    >
                                        <div
                                            v-for="base in cw.bases"
                                            :key="base.id"
                                            class="relative flex min-w-[160px] shrink-0 flex-col gap-2 rounded border p-3 transition-opacity"
                                            :class="
                                                base.inventory_quantity === 0
                                                    ? 'border-surface-200 bg-surface-100 opacity-60'
                                                    : 'border-surface-200 bg-surface-50'
                                            "
                                        >
                                            <div
                                                v-if="isInputDisabled(base)"
                                                class="absolute inset-0 z-10 cursor-not-allowed rounded"
                                                aria-hidden
                                            />
                                            <div
                                                class="text-sm font-medium"
                                                :class="
                                                    base.inventory_quantity ===
                                                    0
                                                        ? 'text-surface-500'
                                                        : 'text-surface-700'
                                                "
                                            >
                                                {{ base.descriptor }}
                                                <span
                                                    v-if="base.weight"
                                                    class="font-normal text-surface-500"
                                                >
                                                    ({{
                                                        formatEnum(base.weight)
                                                    }})
                                                </span>
                                            </div>
                                            <div class="inline-flex">
                                                <UiInputNumber
                                                    :model-value="
                                                        quantities[
                                                            getQuantityKey(
                                                                cw.id,
                                                                base.id,
                                                            )
                                                        ] ?? 0
                                                    "
                                                    :min="0"
                                                    :step="
                                                        cw.per_pan > 0
                                                            ? cw.per_pan
                                                            : 1
                                                    "
                                                    :disabled="
                                                        isInputDisabled(base)
                                                    "
                                                    show-buttons
                                                    button-layout="horizontal"
                                                    size="small"
                                                    @update:model-value="
                                                        quantities[
                                                            getQuantityKey(
                                                                cw.id,
                                                                base.id,
                                                            )
                                                        ] = $event ?? 0
                                                    "
                                                />
                                            </div>
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <p
                                                    class="text-sm"
                                                    :class="
                                                        base.inventory_quantity ===
                                                        0
                                                            ? 'text-surface-500'
                                                            : 'text-primary-600'
                                                    "
                                                >
                                                    {{
                                                        formatCurrency(
                                                            base.wholesale_price,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-surface-500"
                                                >
                                                    {{
                                                        base.inventory_quantity
                                                    }}
                                                    available
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>
                </UiCard>
            </div>

            <!-- Right panel: order details -->
            <div class="lg:sticky lg:top-4 lg:col-span-1 lg:self-start">
                <UiCard>
                    <template #title>Order Details</template>
                    <template #content>
                        <div
                            v-if="!hasVisibleColorways"
                            class="rounded border border-dashed border-surface-200 py-6 text-center text-sm text-surface-500"
                        >
                            Select colorways to see order details
                        </div>
                        <div v-else class="space-y-4">
                            <UiForm
                                :initial-values="{
                                    order_id: saveInitialValues.order_id,
                                    notes: saveInitialValues.notes,
                                }"
                                @submit="handleSaveSubmit"
                            >
                                <div class="text-sm">
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
                                            <span
                                                >{{
                                                    wholesale_terms.minimum_order_quantity
                                                }}
                                                skeins /
                                                {{
                                                    formatCurrency(
                                                        wholesale_terms.minimum_order_value,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="
                                                wholesale_terms.discount_rate !=
                                                null
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
                                                wholesale_terms.lead_time_days !=
                                                null
                                            "
                                            class="flex justify-between"
                                        >
                                            <span class="text-surface-600"
                                                >Lead Time</span
                                            >
                                            <span
                                                >{{
                                                    wholesale_terms.lead_time_days
                                                }}
                                                days</span
                                            >
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

                                    <div class="mt-6 mb-3 flex justify-between">
                                        <span class="text-surface-600"
                                            >Total Skeins</span
                                        >
                                        <span>{{ skeinCount }}</span>
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-surface-600"
                                                >Subtotal</span
                                            >
                                            <span>{{
                                                formatCurrency(subtotal)
                                            }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-surface-600"
                                                >Tax</span
                                            >
                                            <span>{{ formatCurrency(0) }}</span>
                                        </div>
                                        <div
                                            class="flex justify-between border-t border-surface-200 pt-2 font-medium"
                                        >
                                            <span>Total</span>
                                            <span>{{
                                                formatCurrency(total)
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <UiFormField
                                    name="notes"
                                    label="Order notes"
                                    :server-error="saveForm.errors.notes"
                                >
                                    <textarea
                                        v-model="notes"
                                        name="notes"
                                        rows="3"
                                        class="w-full rounded border border-surface-300 bg-surface-0 px-3 py-2 text-sm"
                                        placeholder="Optional notes for this order"
                                    />
                                </UiFormField>

                                <div
                                    class="mt-6 flex flex-wrap items-center justify-between gap-2"
                                >
                                    <UiButton
                                        type="submit"
                                        label="Save as Draft"
                                        severity="secondary"
                                        outlined
                                        :disabled="!hasVisibleColorways"
                                        :loading="saveForm.processing"
                                    />
                                    <UiButton
                                        type="button"
                                        label="Submit Order"
                                        :disabled="
                                            !orderId ||
                                            !minimumsAreMet ||
                                            !hasVisibleColorways
                                        "
                                        @click="handleSubmit"
                                    />
                                </div>
                            </UiForm>
                        </div>
                    </template>
                </UiCard>
            </div>
        </div>
    </StoreLayout>
</template>
