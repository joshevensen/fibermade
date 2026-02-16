<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';
import { Link } from '@inertiajs/vue3';

function orderDetailUrl(orderId: number): string {
    return `/creator/orders/${orderId}`;
}

interface Orderable {
    name: string;
}

interface Order {
    id: number;
    order_date: string;
    total_amount?: number | null;
    orderable?: Orderable | null;
}

const STATUS_GROUPS: { key: string; label: string }[] = [
    { key: 'open', label: 'Pending' },
    { key: 'accepted', label: 'Accepted' },
    { key: 'fulfilled', label: 'Fulfilled' },
    { key: 'delivered', label: 'Delivered' },
];

interface Props {
    /** Orders grouped by status: { open: Order[], accepted: Order[], ... } */
    activeOrders: Record<string, Order[]>;
}

defineProps<Props>();

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '$0.00';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(dateStr));
}

function storeName(order: Order): string {
    return order.orderable?.name ?? '—';
}
</script>

<template>
    <UiCard>
        <template #title> Wholesale Orders </template>
        <template #content>
            <div class="space-y-6">
                <section
                    v-for="group in STATUS_GROUPS"
                    :key="group.key"
                    class="space-y-2"
                >
                    <h3
                        class="text-sm font-semibold tracking-wide text-surface-600 uppercase"
                    >
                        {{ group.label }}
                    </h3>
                    <div
                        v-if="
                            !activeOrders[group.key] ||
                            activeOrders[group.key].length === 0
                        "
                        class="rounded-lg border border-dashed border-surface-200 bg-surface-50 py-4 text-center text-sm text-surface-500 dark:bg-surface-100 dark:text-surface-400"
                    >
                        No orders
                    </div>
                    <ul v-else class="space-y-1">
                        <li
                            v-for="order in activeOrders[group.key]"
                            :key="order.id"
                        >
                            <Link
                                :href="orderDetailUrl(order.id)"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-surface-200 px-3 py-2 text-left transition-colors hover:border-surface-300 hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-100"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-surface-900">
                                        {{ storeName(order) }}
                                    </p>
                                    <p class="text-sm text-surface-600">
                                        #{{ order.id }}
                                        ·
                                        {{ formatDate(order.order_date) }}
                                    </p>
                                </div>
                                <div
                                    class="shrink-0 text-right font-medium text-surface-900"
                                >
                                    {{ formatCurrency(order.total_amount) }}
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
        </template>
    </UiCard>
</template>
