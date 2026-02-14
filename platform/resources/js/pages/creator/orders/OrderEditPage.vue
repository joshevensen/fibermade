<script setup lang="ts">
import { edit as editCustomer } from '@/actions/App/Http/Controllers/CustomerController';
import {
    accept,
    cancel,
    deliver,
    fulfill,
} from '@/actions/App/Http/Controllers/OrderController';
import { edit as editShow } from '@/actions/App/Http/Controllers/ShowController';
import { edit as editStore } from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiTextarea from '@/components/ui/UiTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { orderStatusBadgeClass } from '@/utils/orderStatusBadge';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface OrderItem {
    id: number;
    colorway_id: number;
    base_id: number;
    quantity: number;
    unit_price?: number | null;
    line_total?: number | null;
    colorway?: {
        id: number;
        name: string;
    } | null;
    base?: {
        id: number;
        code: string;
        descriptor: string;
        weight?: { value: string } | string | null;
    } | null;
}

interface OrderableShow {
    id: number;
    name: string;
    location_name?: string | null;
    start_at?: string | null;
    end_at?: string | null;
}

interface OrderableStore {
    id: number;
    name: string;
    email?: string | null;
    owner_name?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    state_region?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
}

interface OrderableCustomer {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    city?: string | null;
    state_region?: string | null;
}

const TRANSITION_LABELS: Record<string, string> = {
    accepted: 'Accept Order',
    fulfilled: 'Mark as Fulfilled',
    delivered: 'Mark as Delivered',
    cancelled: 'Cancel Order',
};

const WORKFLOW_STEPS = [
    { key: 'open', label: 'Open' },
    { key: 'accepted', label: 'Accepted' },
    { key: 'fulfilled', label: 'Fulfilled' },
    { key: 'delivered', label: 'Delivered' },
] as const;

interface WholesaleTerms {
    discount_rate?: number | null;
    payment_terms?: string | null;
    lead_time_days?: number | null;
    minimum_order_quantity?: number | null;
    minimum_order_value?: number | null;
    allows_preorders?: boolean | null;
}

interface Props {
    order: {
        id: number;
        type: string;
        status: string;
        order_date: string;
        shipping_amount?: number | null;
        discount_amount?: number | null;
        tax_amount?: number | null;
        notes?: string | null;
        orderItems?: OrderItem[];
        orderable?: OrderableShow | OrderableStore | OrderableCustomer | null;
    };
    orderTypeOptions: Array<{ label: string; value: string }>;
    orderStatusOptions: Array<{ label: string; value: string }>;
    colorways: Array<{ id: number; name: string }>;
    bases: Array<{ id: number; code: string; descriptor: string }>;
    allowedTransitions: string[];
    wholesaleTerms?: WholesaleTerms | null;
}

const props = defineProps<Props>();
const { require: requireConfirm } = useConfirm();
const { showError } = useToast();

const showTransitionDialog = ref(false);
const pendingTransition = ref<string | null>(null);
const transitionNote = ref('');
const isTransitioning = ref(false);

const primaryTransition = computed(
    () => props.allowedTransitions.find((t) => t !== 'cancelled') ?? null,
);
const canCancel = computed(() =>
    props.allowedTransitions.includes('cancelled'),
);
const transitionDialogTitle = computed(() => {
    const t = pendingTransition.value;
    if (!t || !TRANSITION_LABELS[t]) return 'Confirm';
    return `${TRANSITION_LABELS[t]}?`;
});

const currentStepIndex = computed(() => {
    const idx = WORKFLOW_STEPS.findIndex((s) => s.key === props.order.status);
    return idx >= 0 ? idx : 0;
});

function openTransitionDialog(transition: string): void {
    pendingTransition.value = transition;
    transitionNote.value = '';
    showTransitionDialog.value = true;
}

function closeTransitionDialog(): void {
    showTransitionDialog.value = false;
    pendingTransition.value = null;
    transitionNote.value = '';
}

function getTransitionUrl(transition: string): string {
    const id = props.order.id;
    if (transition === 'accepted') return accept.url(id);
    if (transition === 'fulfilled') return fulfill.url(id);
    if (transition === 'delivered') return deliver.url(id);
    return '';
}

function submitTransition(): void {
    const transition = pendingTransition.value;
    if (!transition || transition === 'cancelled') return;
    const url = getTransitionUrl(transition);
    if (!url) return;
    isTransitioning.value = true;
    router.patch(
        url,
        { note: transitionNote.value },
        {
            onSuccess: () => {
                closeTransitionDialog();
                isTransitioning.value = false;
            },
            onError: (errors) => {
                const message =
                    typeof errors === 'object' && errors && 'message' in errors
                        ? String((errors as { message: string }).message)
                        : 'The transition could not be completed.';
                showError(message);
                closeTransitionDialog();
                isTransitioning.value = false;
            },
        },
    );
}

function handleCancelOrder(event: Event): void {
    requireConfirm({
        target: event.currentTarget as HTMLElement,
        message:
            'Cancel this order? The customer will no longer be able to use it for fulfillment.',
        header: 'Cancel Order',
        acceptLabel: 'Cancel Order',
        acceptSeverity: 'danger',
        accept: () => {
            isTransitioning.value = true;
            router.patch(
                cancel.url(props.order.id),
                {},
                {
                    onSuccess: () => {
                        isTransitioning.value = false;
                    },
                    onError: (errors) => {
                        const message =
                            typeof errors === 'object' &&
                            errors &&
                            'message' in errors
                                ? String(
                                      (errors as { message: string }).message,
                                  )
                                : 'The order could not be cancelled.';
                        showError(message);
                        isTransitioning.value = false;
                    },
                },
            );
        },
    });
}

function stepStatus(stepIndex: number): 'completed' | 'current' | 'upcoming' {
    if (stepIndex < currentStepIndex.value) return 'completed';
    if (stepIndex === currentStepIndex.value) return 'current';
    return 'upcoming';
}

const calculatedTotals = computed(() => {
    const subtotal =
        props.order.orderItems?.reduce(
            (sum, item) => sum + (item.line_total || 0),
            0,
        ) || 0;
    const shipping = props.order.shipping_amount || 0;
    const discount = props.order.discount_amount || 0;
    const tax = props.order.tax_amount || 0;
    const total = subtotal + shipping - discount + tax;

    return {
        subtotal,
        shipping,
        discount,
        tax,
        total,
    };
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
}

function formatStatus(status: string): string {
    return status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatStoreAddress(store: OrderableStore): string {
    const parts = [
        store.address_line1,
        store.address_line2,
        [store.city, store.state_region].filter(Boolean).join(', '),
        store.postal_code,
        store.country_code,
    ].filter(Boolean);
    return parts.length > 0 ? parts.join('\n') : '—';
}

function formatBaseDisplay(base: OrderItem['base']): string {
    if (!base) return 'N/A';
    const descriptor = base.descriptor || base.code || '';
    const weightRaw = base.weight;
    const weight =
        typeof weightRaw === 'string'
            ? weightRaw
            : weightRaw && typeof weightRaw === 'object' && 'value' in weightRaw
              ? (weightRaw as { value: string }).value
              : null;
    const weightDisplay = weight
        ? ` - ${weight.charAt(0).toUpperCase() + weight.slice(1)}`
        : '';
    return descriptor ? `${descriptor}${weightDisplay}` : base.code || 'N/A';
}
</script>

<template>
    <CreatorLayout page-title="Order Details">
        <template #default>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #content>
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Status
                                </label>
                                <span
                                    class="inline-block rounded-full px-3 py-1 text-sm font-medium"
                                    :class="
                                        orderStatusBadgeClass(
                                            props.order.status,
                                        )
                                    "
                                >
                                    {{ formatStatus(props.order.status) }}
                                </span>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Order Date
                                </label>
                                <p class="text-base text-surface-900">
                                    {{ formatDate(props.order.order_date) }}
                                </p>
                            </div>

                            <div v-if="props.order.notes">
                                <label
                                    class="mb-1 block text-sm font-medium text-surface-700"
                                >
                                    Notes
                                </label>
                                <div
                                    class="prose prose-sm max-w-none text-base text-surface-900"
                                    v-html="props.order.notes"
                                />
                            </div>

                            <div
                                v-if="
                                    props.order.status !== 'draft' &&
                                    props.order.status !== 'cancelled'
                                "
                                class="pt-4"
                            >
                                <label
                                    class="mb-2 block text-sm font-medium text-surface-700"
                                >
                                    Progress
                                </label>
                                <div
                                    class="flex flex-wrap items-center gap-2 sm:gap-4"
                                >
                                    <template
                                        v-for="(step, index) in WORKFLOW_STEPS"
                                        :key="step.key"
                                    >
                                        <div
                                            v-if="index > 0"
                                            class="h-px w-4 shrink-0 bg-surface-300 sm:w-6"
                                            aria-hidden="true"
                                        />
                                        <div
                                            class="flex items-center gap-2"
                                            :class="{
                                                'text-surface-500':
                                                    stepStatus(index) ===
                                                    'upcoming',
                                                'font-medium text-surface-700':
                                                    stepStatus(index) ===
                                                    'current',
                                                'text-surface-600':
                                                    stepStatus(index) ===
                                                    'completed',
                                            }"
                                        >
                                            <span
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs"
                                                :class="{
                                                    'bg-primary text-primary-contrast':
                                                        stepStatus(index) ===
                                                        'current',
                                                    'bg-surface-200 text-surface-600':
                                                        stepStatus(index) ===
                                                        'upcoming',
                                                    'bg-surface-200 text-surface-700':
                                                        stepStatus(index) ===
                                                        'completed',
                                                }"
                                            >
                                                <template
                                                    v-if="
                                                        stepStatus(index) ===
                                                        'completed'
                                                    "
                                                    >✓</template
                                                >
                                                <template v-else>{{
                                                    index + 1
                                                }}</template>
                                            </span>
                                            <span class="text-sm">{{
                                                step.label
                                            }}</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div
                                v-if="
                                    props.order.status === 'cancelled' &&
                                    props.order.status !== 'draft'
                                "
                                class="pt-4"
                            >
                                <span
                                    class="inline-block rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800"
                                >
                                    Cancelled
                                </span>
                            </div>

                            <div
                                v-if="
                                    props.allowedTransitions &&
                                    props.allowedTransitions.length > 0
                                "
                                class="flex flex-wrap gap-2 pt-4"
                            >
                                <UiButton
                                    v-if="primaryTransition"
                                    :label="
                                        TRANSITION_LABELS[primaryTransition]
                                    "
                                    :disabled="isTransitioning"
                                    @click="
                                        openTransitionDialog(primaryTransition)
                                    "
                                />
                                <UiButton
                                    v-if="canCancel"
                                    label="Cancel Order"
                                    severity="danger"
                                    :disabled="isTransitioning"
                                    outlined
                                    @click="handleCancelOrder"
                                />
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiDialog
                    :visible="showTransitionDialog"
                    modal
                    :header="transitionDialogTitle"
                    :closable="true"
                    :show-header="true"
                    @update:visible="
                        (v) => {
                            if (!v) closeTransitionDialog();
                        }
                    "
                >
                    <div class="space-y-4">
                        <p class="text-surface-700">
                            Add an optional note for this status change.
                        </p>
                        <UiTextarea
                            v-model="transitionNote"
                            placeholder="Optional note (max 1000 characters)"
                            :auto-resize="true"
                            maxlength="1000"
                        />
                    </div>
                    <template #footer>
                        <UiButton
                            label="Cancel"
                            severity="secondary"
                            outlined
                            @click="closeTransitionDialog"
                        />
                        <UiButton
                            label="Confirm"
                            :disabled="isTransitioning"
                            @click="submitTransition"
                        />
                    </template>
                </UiDialog>

                <UiCard>
                    <template #header>
                        <h3 class="text-lg font-semibold">Order Items</h3>
                    </template>
                    <template #content>
                        <div
                            v-if="
                                !order.orderItems ||
                                order.orderItems.length === 0
                            "
                            class="py-8 text-center text-surface-500"
                        >
                            No order items
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-surface-200">
                                        <th
                                            class="px-4 py-2 text-left text-sm font-semibold text-surface-700"
                                        >
                                            Colorway
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-sm font-semibold text-surface-700"
                                        >
                                            Base
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Quantity
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Unit Price
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-semibold text-surface-700"
                                        >
                                            Line Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in order.orderItems"
                                        :key="item.id"
                                        class="border-b border-surface-100"
                                    >
                                        <td class="px-4 py-2 text-sm">
                                            {{ item.colorway?.name || 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ formatBaseDisplay(item.base) }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm"
                                        >
                                            {{ item.quantity }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm"
                                        >
                                            {{
                                                item.unit_price
                                                    ? formatCurrency(
                                                          item.unit_price,
                                                      )
                                                    : 'N/A'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right text-sm font-medium"
                                        >
                                            {{
                                                item.line_total
                                                    ? formatCurrency(
                                                          item.line_total,
                                                      )
                                                    : 'N/A'
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard v-if="order.orderable">
                    <template #header>
                        <h3 class="text-lg font-semibold">
                            {{
                                order.type === 'show'
                                    ? 'Show'
                                    : order.type === 'wholesale'
                                      ? 'Store'
                                      : 'Customer'
                            }}
                        </h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-if="order.type === 'show'">
                                <Link
                                    :href="
                                        editShow.url(
                                            (order.orderable as OrderableShow)
                                                .id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableShow).name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableShow)
                                            .location_name
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableShow)
                                            .location_name
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableShow)
                                            .start_at
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        new Date(
                                            (order.orderable as OrderableShow)
                                                .start_at!,
                                        ).toLocaleDateString()
                                    }}
                                    <span
                                        v-if="
                                            (order.orderable as OrderableShow)
                                                .end_at
                                        "
                                    >
                                        -
                                        {{
                                            new Date(
                                                (
                                                    order.orderable as OrderableShow
                                                ).end_at!,
                                            ).toLocaleDateString()
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="order.type === 'wholesale'">
                                <Link
                                    :href="
                                        editStore.url(
                                            (order.orderable as OrderableStore)
                                                .id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableStore).name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableStore)
                                            .owner_name
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableStore)
                                            .owner_name
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableStore)
                                            .email
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableStore)
                                            .email
                                    }}
                                </div>
                                <div
                                    v-if="
                                        formatStoreAddress(
                                            order.orderable as OrderableStore,
                                        ) !== '—'
                                    "
                                    class="mt-2 text-sm whitespace-pre-line text-surface-600"
                                >
                                    {{
                                        formatStoreAddress(
                                            order.orderable as OrderableStore,
                                        )
                                    }}
                                </div>
                            </div>
                            <div v-else>
                                <Link
                                    :href="
                                        editCustomer.url(
                                            (
                                                order.orderable as OrderableCustomer
                                            ).id,
                                        ).url
                                    "
                                    class="text-xl font-semibold text-primary hover:underline"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .name
                                    }}
                                </Link>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .email
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .email
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .phone
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        (order.orderable as OrderableCustomer)
                                            .phone
                                    }}
                                </div>
                                <div
                                    v-if="
                                        (order.orderable as OrderableCustomer)
                                            .city ||
                                        (order.orderable as OrderableCustomer)
                                            .state_region
                                    "
                                    class="mt-2 text-sm text-surface-600"
                                >
                                    {{
                                        [
                                            (
                                                order.orderable as OrderableCustomer
                                            ).city,
                                            (
                                                order.orderable as OrderableCustomer
                                            ).state_region,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </div>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard v-if="order.type === 'wholesale' && wholesaleTerms">
                    <template #header>
                        <h3 class="text-lg font-semibold">Wholesale Terms</h3>
                    </template>
                    <template #content>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600"
                                    >Discount rate:</span
                                >
                                <span class="font-medium">
                                    {{
                                        wholesaleTerms.discount_rate != null
                                            ? `${(wholesaleTerms.discount_rate * 100).toFixed(1)}%`
                                            : '—'
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600"
                                    >Payment terms:</span
                                >
                                <span class="font-medium">
                                    {{ wholesaleTerms.payment_terms ?? '—' }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Lead time:</span>
                                <span class="font-medium">
                                    {{
                                        wholesaleTerms.lead_time_days != null
                                            ? `${wholesaleTerms.lead_time_days} days`
                                            : '—'
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600"
                                    >Min. order quantity:</span
                                >
                                <span class="font-medium">
                                    {{
                                        wholesaleTerms.minimum_order_quantity ??
                                        '—'
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600"
                                    >Min. order value:</span
                                >
                                <span class="font-medium">
                                    {{
                                        wholesaleTerms.minimum_order_value !=
                                        null
                                            ? formatCurrency(
                                                  wholesaleTerms.minimum_order_value,
                                              )
                                            : '—'
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600"
                                    >Preorder allowed:</span
                                >
                                <span class="font-medium">
                                    {{
                                        wholesaleTerms.allows_preorders != null
                                            ? wholesaleTerms.allows_preorders
                                                ? 'Yes'
                                                : 'No'
                                            : '—'
                                    }}
                                </span>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #header>
                        <h3 class="text-lg font-semibold">Totals</h3>
                    </template>
                    <template #content>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Subtotal:</span>
                                <span class="font-medium">
                                    {{
                                        formatCurrency(
                                            calculatedTotals.subtotal,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Shipping:</span>
                                <span class="font-medium">
                                    {{
                                        formatCurrency(
                                            calculatedTotals.shipping,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Discount:</span>
                                <span class="font-medium text-red-600">
                                    -{{
                                        formatCurrency(
                                            calculatedTotals.discount,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-surface-600">Tax:</span>
                                <span class="font-medium">
                                    {{ formatCurrency(calculatedTotals.tax) }}
                                </span>
                            </div>
                            <div class="border-t border-surface-200 pt-2">
                                <div class="flex justify-between">
                                    <span class="text-base font-semibold"
                                        >Total:</span
                                    >
                                    <span class="text-lg font-bold">
                                        {{
                                            formatCurrency(
                                                calculatedTotals.total,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <!-- TODO: Re-enable delete functionality when ready to work on orders -->
                <!-- <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this order will permanently remove
                                    all associated data. This action cannot be
                                    undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Order
                            </UiButton>
                        </div>
                    </template>
                </UiCard> -->
            </div>
        </template>
    </CreatorLayout>

    <!-- TODO: Re-enable OrderItemDrawer when ready to work on orders -->
    <!-- <OrderItemDrawer
        :visible="showOrderItemDrawer"
        :order-id="order.id"
        :order-item="editingOrderItem"
        :colorways="colorways"
        :bases="bases"
        @update:visible="closeOrderItemDrawer"
    /> -->
</template>
