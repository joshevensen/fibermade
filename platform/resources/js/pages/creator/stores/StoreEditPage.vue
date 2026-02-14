<script setup lang="ts">
import { edit as editOrder } from '@/actions/App/Http/Controllers/OrderController';
import {
    destroy as destroyStore,
    update,
    updateStatus,
} from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useToast } from '@/composables/useToast';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { orderStatusBadgeClass } from '@/utils/orderStatusBadge';
import { relationshipStatusBadgeClass } from '@/utils/relationshipStatusBadge';
import { router } from '@inertiajs/vue3';

interface Props {
    store: {
        id: number;
        name: string;
        email: string;
        owner_name?: string | null;
        address_line1: string;
        address_line2?: string | null;
        city: string;
        state_region: string;
        postal_code: string;
        country_code: string;
        discount_rate?: number | null;
        minimum_order_quantity?: number | null;
        minimum_order_value?: number | null;
        payment_terms?: string | null;
        lead_time_days?: number | null;
        allows_preorders: boolean;
        status: string;
        notes?: string | null;
    };
    orders: Array<{
        id: number;
        order_date: string;
        status: string;
        total_amount?: number | null;
        skein_count: number;
    }>;
    ordersTruncated?: boolean;
}

const props = defineProps<Props>();
const { require, requireDelete } = useConfirm();
const { showSuccess } = useToast();

const pivotFieldKeys = [
    'discount_rate',
    'payment_terms',
    'minimum_order_quantity',
    'minimum_order_value',
    'lead_time_days',
    'allows_preorders',
    'notes',
] as const;

const initialFormValues: Record<(typeof pivotFieldKeys)[number], unknown> = {
    discount_rate: props.store.discount_rate ?? null,
    payment_terms: props.store.payment_terms ?? null,
    minimum_order_quantity: props.store.minimum_order_quantity ?? null,
    minimum_order_value: props.store.minimum_order_value ?? null,
    lead_time_days: props.store.lead_time_days ?? null,
    allows_preorders: props.store.allows_preorders ?? false,
    notes: props.store.notes ?? null,
};

function submitStatus(newStatus: string): void {
    router.patch(updateStatus.url(props.store.id), { status: newStatus });
}

function handlePause(event: Event): void {
    require({
        target: event.currentTarget as HTMLElement,
        message:
            'Pausing will prevent the store from placing new orders. Continue?',
        acceptLabel: 'Pause',
        accept: () => submitStatus('paused'),
    });
}

function handleEndRelationship(event: Event): void {
    require({
        target: event.currentTarget as HTMLElement,
        message:
            'Ending this relationship is permanent. The store will lose access to your catalog. Continue?',
        acceptLabel: 'End relationship',
        acceptSeverity: 'danger',
        accept: () => submitStatus('ended'),
    });
}

const { form: vendorForm } = useFormSubmission({
    route: () => update(props.store.id),
    initialValues: initialFormValues,
    successMessage: 'Store settings updated successfully.',
    onSuccess: () => {
        router.visit('/stores');
    },
});

function onVendorSubmit({
    valid,
    values,
}: {
    valid: boolean;
    values: Record<string, any>;
}): void {
    if (!valid) {
        return;
    }

    const pivotPayload: Record<string, unknown> = {};
    for (const key of pivotFieldKeys) {
        pivotPayload[key] = values[key] ?? initialFormValues[key];
    }
    Object.assign(vendorForm, pivotPayload);

    vendorForm.submit(update(props.store.id), {
        onSuccess: () => {
            showSuccess('Store settings updated successfully.');
            router.visit('/stores');
        },
    });
}

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.store.name}?`,
        onAccept: () => {
            router.delete(destroyStore.url(props.store.id));
        },
    });
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
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

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatAddress(): string {
    const parts = [
        props.store.address_line1,
        props.store.address_line2,
        [props.store.city, props.store.state_region].filter(Boolean).join(', '),
        props.store.postal_code,
        props.store.country_code,
    ].filter(Boolean);
    return parts.length > 0 ? parts.join('\n') : '—';
}
</script>

<template>
    <CreatorLayout page-title="Store Details">
        <template #default>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Store Information</h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-sm font-medium text-surface-600"
                                >
                                    Store Name
                                </label>
                                <p class="mt-1 text-surface-900">
                                    {{ props.store.name || '—' }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="text-sm font-medium text-surface-600"
                                >
                                    Email
                                </label>
                                <p class="mt-1 text-surface-900">
                                    {{ props.store.email || '—' }}
                                </p>
                            </div>

                            <div v-if="props.store.owner_name">
                                <label
                                    class="text-sm font-medium text-surface-600"
                                >
                                    Owner Name
                                </label>
                                <p class="mt-1 text-surface-900">
                                    {{ props.store.owner_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="text-sm font-medium text-surface-600"
                                >
                                    Location
                                </label>
                                <p
                                    class="mt-1 whitespace-pre-line text-surface-900"
                                >
                                    {{ formatAddress() }}
                                </p>
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">
                            Wholesale Settings
                        </h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-sm font-medium text-surface-600"
                                >
                                    Relationship status
                                </label>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            relationshipStatusBadgeClass(
                                                props.store.status,
                                            )
                                        "
                                    >
                                        {{ formatEnum(props.store.status) }}
                                    </span>
                                    <template
                                        v-if="props.store.status === 'active'"
                                    >
                                        <UiButton
                                            type="button"
                                            size="small"
                                            outlined
                                            @click="handlePause($event)"
                                        >
                                            Pause Relationship
                                        </UiButton>
                                    </template>
                                    <template
                                        v-else-if="
                                            props.store.status === 'paused'
                                        "
                                    >
                                        <UiButton
                                            type="button"
                                            size="small"
                                            @click="submitStatus('active')"
                                        >
                                            Reactivate
                                        </UiButton>
                                        <UiButton
                                            type="button"
                                            size="small"
                                            severity="danger"
                                            outlined
                                            @click="
                                                handleEndRelationship($event)
                                            "
                                        >
                                            End Relationship
                                        </UiButton>
                                    </template>
                                </div>
                            </div>

                            <UiForm
                                :initial-values="initialFormValues"
                                @submit="onVendorSubmit"
                            >
                                <div class="grid grid-cols-2 gap-4">
                                    <UiFormField
                                        name="discount_rate"
                                        label="Discount Rate (%)"
                                        :server-error="
                                            vendorForm.errors.discount_rate
                                        "
                                    >
                                        <template
                                            #default="{ props: fieldProps }"
                                        >
                                            <UiInputGroup>
                                                <UiInputNumber
                                                    v-bind="fieldProps"
                                                    :min="0"
                                                    :max="100"
                                                    :step="0.01"
                                                />
                                                <UiInputGroupAddon
                                                    >%</UiInputGroupAddon
                                                >
                                            </UiInputGroup>
                                        </template>
                                    </UiFormField>

                                    <UiFormFieldInput
                                        name="payment_terms"
                                        label="Deposit"
                                        placeholder="e.g., Net 30, Net 60"
                                        :server-error="
                                            vendorForm.errors.payment_terms
                                        "
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <UiFormFieldInputNumber
                                        name="minimum_order_quantity"
                                        label="Min Quantity"
                                        :min="1"
                                        :server-error="
                                            vendorForm.errors
                                                .minimum_order_quantity
                                        "
                                    />

                                    <UiFormField
                                        name="minimum_order_value"
                                        label="Min Value"
                                        :server-error="
                                            vendorForm.errors
                                                .minimum_order_value
                                        "
                                    >
                                        <template
                                            #default="{ props: fieldProps }"
                                        >
                                            <UiInputGroup>
                                                <UiInputGroupAddon
                                                    >$</UiInputGroupAddon
                                                >
                                                <UiInputNumber
                                                    v-bind="fieldProps"
                                                    :min="0"
                                                    :max="99999999.99"
                                                    :step="0.01"
                                                />
                                            </UiInputGroup>
                                        </template>
                                    </UiFormField>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <UiFormFieldInputNumber
                                        name="lead_time_days"
                                        label="Lead Time"
                                        :min="0"
                                        :server-error="
                                            vendorForm.errors.lead_time_days
                                        "
                                    />

                                    <UiFormField
                                        name="allows_preorders"
                                        label="Allow Preorder"
                                        :server-error="
                                            vendorForm.errors.allows_preorders
                                        "
                                    >
                                        <template
                                            #default="{ props: fieldProps }"
                                        >
                                            <UiSelectButton
                                                v-bind="fieldProps"
                                                :options="[
                                                    {
                                                        label: 'Yes',
                                                        value: true,
                                                    },
                                                    {
                                                        label: 'No',
                                                        value: false,
                                                    },
                                                ]"
                                                size="small"
                                                fluid
                                            />
                                        </template>
                                    </UiFormField>
                                </div>

                                <UiFormFieldInput
                                    name="notes"
                                    label="Notes"
                                    placeholder="Internal notes about this store"
                                    :server-error="vendorForm.errors.notes"
                                />

                                <UiButton
                                    type="submit"
                                    :loading="vendorForm.processing"
                                >
                                    Update Settings
                                </UiButton>
                            </UiForm>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Orders</h3>
                    </template>
                    <template #content>
                        <p
                            v-if="ordersTruncated"
                            class="mb-2 text-xs text-surface-500"
                        >
                            Showing 100 most recent orders
                        </p>
                        <div v-if="props.orders.length === 0" class="py-8">
                            <p class="text-center text-surface-500">
                                No orders yet
                            </p>
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="order in props.orders"
                                :key="order.id"
                                class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                            >
                                <div class="flex flex-1 flex-col gap-1">
                                    <button
                                        class="text-left font-medium text-surface-700 hover:text-primary-600"
                                        @click="
                                            router.visit(
                                                editOrder.url(order.id),
                                            )
                                        "
                                    >
                                        {{ formatDate(order.order_date) }}
                                    </button>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                            :class="
                                                orderStatusBadgeClass(
                                                    order.status,
                                                )
                                            "
                                        >
                                            {{ formatEnum(order.status) }}
                                        </span>
                                        <span class="text-xs text-surface-600">
                                            {{ order.skein_count }} skeins
                                        </span>
                                    </div>
                                </div>
                                <span
                                    v-if="order.total_amount != null"
                                    class="text-sm font-medium text-surface-700"
                                >
                                    {{ formatCurrency(order.total_amount) }}
                                </span>
                            </li>
                        </ul>
                    </template>
                </UiCard>

                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this store will permanently remove
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
                                Delete Store
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </CreatorLayout>
</template>
