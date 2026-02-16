<script setup lang="ts">
import {
    saveOrder,
    submitOrder,
} from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
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
    primary_image_url: string | null;
    bases: BaseItem[];
}

interface WholesaleTerms {
    discount_rate: number | null;
    minimum_order_quantity: number | null;
    minimum_order_value: number | null;
    allows_preorders: boolean;
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

const visibleColorways = computed(() =>
    props.colorways.filter((cw) => visibleColorwayIds.value.includes(cw.id)),
);

const skeinCount = computed(() => {
    return Object.values(quantities.value).reduce((sum, qty) => sum + qty, 0);
});

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
    return (
        !props.wholesale_terms.allows_preorders && base.inventory_quantity === 0
    );
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
        <div class="space-y-6">
            <h1 class="text-2xl font-semibold">
                Base &amp; Quantity Selection
            </h1>
            <p class="text-muted-foreground">
                Order for {{ props.creator.name }}
            </p>

            <div v-if="!hasVisibleColorways" class="space-y-4">
                <div
                    class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                >
                    No colorways selected. Go back to select colorways.
                </div>
                <UiButton label="Back" severity="secondary" @click="goBack" />
            </div>

            <template v-else>
                <div class="space-y-6">
                    <UiCard
                        v-for="cw in visibleColorways"
                        :key="cw.id"
                        class="overflow-hidden"
                    >
                        <template #title>
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="h-16 w-16 shrink-0 overflow-hidden rounded bg-surface-200"
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
                                    <span class="font-medium">{{
                                        cw.name
                                    }}</span>
                                </div>
                                <UiButton
                                    label="Remove"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="removeColorway(cw.id)"
                                />
                            </div>
                        </template>

                        <template #content>
                            <div
                                class="flex flex-col gap-4 sm:flex-row sm:flex-wrap"
                            >
                                <div
                                    v-for="base in cw.bases"
                                    :key="base.id"
                                    class="flex min-w-0 flex-1 flex-col gap-1 rounded border border-surface-200 bg-surface-50 p-3 sm:min-w-[180px]"
                                >
                                    <div
                                        class="text-sm font-medium text-surface-700"
                                    >
                                        {{ base.descriptor }}
                                        <span
                                            v-if="base.weight"
                                            class="font-normal text-surface-500"
                                        >
                                            ({{ formatEnum(base.weight) }})
                                        </span>
                                    </div>
                                    <div class="text-sm text-primary-600">
                                        {{
                                            formatCurrency(base.wholesale_price)
                                        }}
                                    </div>
                                    <input
                                        v-model.number="
                                            quantities[
                                                getQuantityKey(cw.id, base.id)
                                            ]
                                        "
                                        type="number"
                                        min="0"
                                        step="1"
                                        :disabled="isInputDisabled(base)"
                                        class="w-20 rounded border border-surface-300 bg-surface-0 px-2 py-1 text-sm disabled:bg-surface-100 disabled:opacity-60"
                                    />
                                    <div class="text-xs text-surface-500">
                                        {{ base.inventory_quantity }} available
                                    </div>
                                </div>
                            </div>
                        </template>
                    </UiCard>
                </div>

                <UiCard>
                    <template #title>Order details</template>
                    <template #content>
                        <div class="space-y-4">
                            <UiForm
                                :initial-values="{
                                    order_id: saveInitialValues.order_id,
                                    notes: saveInitialValues.notes,
                                }"
                                @submit="handleSaveSubmit"
                            >
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

                                <div class="space-y-2">
                                    <div
                                        v-if="
                                            wholesale_terms.minimum_order_quantity !=
                                            null
                                        "
                                        class="text-sm"
                                        :class="
                                            skeinCount >=
                                            (wholesale_terms.minimum_order_quantity ??
                                                0)
                                                ? 'text-green-600'
                                                : 'text-surface-600'
                                        "
                                    >
                                        Skeins: {{ skeinCount }} /
                                        {{
                                            wholesale_terms.minimum_order_quantity
                                        }}
                                    </div>
                                    <div
                                        v-if="
                                            wholesale_terms.minimum_order_value !=
                                            null
                                        "
                                        class="text-sm"
                                        :class="
                                            subtotal >=
                                            (wholesale_terms.minimum_order_value ??
                                                0)
                                                ? 'text-green-600'
                                                : 'text-surface-600'
                                        "
                                    >
                                        Total:
                                        {{ formatCurrency(subtotal) }} /
                                        {{
                                            formatCurrency(
                                                wholesale_terms.minimum_order_value,
                                            )
                                        }}
                                    </div>
                                    <div
                                        v-if="minQtyError || minValError"
                                        class="text-sm text-red-600"
                                    >
                                        <span v-if="minQtyError">{{
                                            minQtyError
                                        }}</span>
                                        <span
                                            v-if="minQtyError && minValError"
                                            class="mx-1"
                                        >
                                            &bull;
                                        </span>
                                        <span v-if="minValError">{{
                                            minValError
                                        }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-1 text-sm">
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
                                            >Shipping</span
                                        >
                                        <span>{{ formatCurrency(0) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-surface-600"
                                            >Discount</span
                                        >
                                        <span>{{ formatCurrency(0) }}</span>
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
                                        <span>{{ formatCurrency(total) }}</span>
                                    </div>
                                </div>

                                <div
                                    class="mt-6 flex flex-wrap items-center gap-3"
                                >
                                    <UiButton
                                        label="Back"
                                        severity="secondary"
                                        outlined
                                        @click="goBack"
                                    />
                                    <UiButton
                                        type="submit"
                                        label="Save as Draft"
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
            </template>
        </div>
    </StoreLayout>
</template>
