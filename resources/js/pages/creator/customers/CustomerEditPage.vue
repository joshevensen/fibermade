<!-- TODO: Restore edit functionality in Stage 2 -->
<script setup lang="ts">
// TODO: Re-enable these imports in Stage 2
// import {
//     destroy as destroyCustomer,
//     update,
// } from '@/actions/App/Http/Controllers/CustomerController';
import { edit as editOrder } from '@/actions/App/Http/Controllers/OrderController';
// import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
// import UiEditor from '@/components/ui/UiEditor.vue';
// import UiForm from '@/components/ui/UiForm.vue';
// import UiFormField from '@/components/ui/UiFormField.vue';
// import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
// import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
// import { useConfirm } from '@/composables/useConfirm';
// import { useFormSubmission } from '@/composables/useFormSubmission';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    customer: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        address_line1?: string | null;
        address_line2?: string | null;
        city?: string | null;
        state_region?: string | null;
        postal_code?: string | null;
        country_code?: string | null;
        notes?: string | null;
    };
    orders: Array<{
        id: number;
        order_date: string;
        status: string;
        total_amount?: number | null;
        orderable?: {
            name: string;
        } | null;
    }>;
}

const props = defineProps<Props>();
// TODO: Re-enable delete functionality in Stage 2
// const { requireDelete } = useConfirm();

// TODO: Re-enable form submission in Stage 2
// const { form, onSubmit } = useFormSubmission({
//     route: () => update(props.customer.id),
//     initialValues: {
//         name: props.customer.name || '',
//         email: props.customer.email || null,
//         phone: props.customer.phone || null,
//         address_line1: props.customer.address_line1 || null,
//         address_line2: props.customer.address_line2 || null,
//         city: props.customer.city || null,
//         state_region: props.customer.state_region || null,
//         postal_code: props.customer.postal_code || null,
//         country_code: props.customer.country_code || null,
//         notes: props.customer.notes || null,
//     },
//     successMessage: 'Customer updated successfully.',
//     onSuccess: () => {
//         router.visit('/customers');
//     },
// });

// TODO: Re-enable delete handler in Stage 2
// function handleDelete(event: Event): void {
//     requireDelete({
//         target: event.currentTarget as HTMLElement,
//         message: `Are you sure you want to delete ${props.customer.name}?`,
//         onAccept: () => {
//             router.delete(destroyCustomer.url(props.customer.id));
//         },
//     });
// }

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
</script>

<template>
    <CreatorLayout page-title="Customer Details">
        <template #default>
            <UiCard>
                <template #content>
                    <div class="space-y-6">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-surface-700"
                            >
                                Name
                            </label>
                            <p class="text-base text-surface-900">
                                {{ props.customer.name || '—' }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-surface-700"
                            >
                                Email
                            </label>
                            <p class="text-base text-surface-900">
                                {{ props.customer.email || '—' }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-surface-700"
                            >
                                Phone
                            </label>
                            <p class="text-base text-surface-900">
                                {{ props.customer.phone || '—' }}
                            </p>
                        </div>

                        <UiDivider />

                        <div>
                            <h3 class="mb-4 text-lg font-semibold">Address</h3>
                            <div class="space-y-2">
                                <p
                                    v-if="props.customer.address_line1"
                                    class="text-base text-surface-900"
                                >
                                    {{ props.customer.address_line1 }}
                                </p>
                                <p
                                    v-if="props.customer.address_line2"
                                    class="text-base text-surface-900"
                                >
                                    {{ props.customer.address_line2 }}
                                </p>
                                <p
                                    v-if="
                                        props.customer.city ||
                                        props.customer.state_region ||
                                        props.customer.postal_code
                                    "
                                    class="text-base text-surface-900"
                                >
                                    {{
                                        [
                                            props.customer.city,
                                            props.customer.state_region,
                                            props.customer.postal_code,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </p>
                                <p
                                    v-if="props.customer.country_code"
                                    class="text-base text-surface-900"
                                >
                                    {{ props.customer.country_code }}
                                </p>
                            </div>
                        </div>

                        <UiDivider />

                        <div v-if="props.customer.notes">
                            <label
                                class="mb-1 block text-sm font-medium text-surface-700"
                            >
                                Notes
                            </label>
                            <div
                                class="prose prose-sm max-w-none text-base text-surface-900"
                                v-html="props.customer.notes"
                            />
                        </div>
                    </div>
                </template>
            </UiCard>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Orders</h3>
                    </template>
                    <template #content>
                        <div v-if="props.orders.length === 0" class="py-8">
                            <p class="text-center text-surface-500">
                                No orders found
                            </p>
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="order in props.orders"
                                :key="order.id"
                                class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                            >
                                <div class="flex items-center gap-3">
                                    <button
                                        class="flex-1 text-left font-medium text-surface-700 hover:text-primary-600"
                                        @click="
                                            router.visit(
                                                editOrder.url(order.id),
                                            )
                                        "
                                    >
                                        {{ formatDate(order.order_date) }}
                                    </button>
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                order.status === 'completed',
                                            'bg-yellow-100 text-yellow-800':
                                                order.status === 'pending',
                                            'bg-blue-100 text-blue-800':
                                                order.status === 'processing',
                                            'bg-red-100 text-red-800':
                                                order.status === 'cancelled',
                                        }"
                                    >
                                        {{ formatEnum(order.status) }}
                                    </span>
                                </div>
                                <span
                                    v-if="order.total_amount"
                                    class="text-sm font-medium text-surface-700"
                                >
                                    {{ formatCurrency(order.total_amount) }}
                                </span>
                            </li>
                        </ul>
                    </template>
                </UiCard>

                <!-- TODO: Re-enable delete functionality in Stage 2 -->
                <!-- <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this customer will permanently
                                    remove all associated data, including
                                    orders. This action cannot be undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Customer
                            </UiButton>
                        </div>
                    </template>
                </UiCard> -->
            </div>
        </template>
    </CreatorLayout>
</template>
