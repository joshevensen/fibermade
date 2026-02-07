<script setup lang="ts">
import { edit as editOrder } from '@/actions/App/Http/Controllers/OrderController';
import UiCard from '@/components/ui/UiCard.vue';
import { router } from '@inertiajs/vue3';

interface Order {
    id: number;
    total_amount?: number | null;
    orderable?: {
        name: string;
    } | null;
}

interface Props {
    openOrders: Order[];
}

const props = defineProps<Props>();

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '$0.00';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function handleOrderClick(order: Order): void {
    router.visit(editOrder.url(order.id));
}
</script>

<template>
    <UiCard>
        <template #title> Open Orders </template>
        <template #content>
            <div class="space-y-4">
                <div v-if="openOrders.length === 0" class="text-surface-500">
                    No open orders
                </div>
                <div v-else class="space-y-0">
                    <button
                        v-for="(order, index) in openOrders"
                        :key="order.id"
                        type="button"
                        class="flex w-full items-center justify-between border-b border-surface-200 py-3 text-left transition-colors first:pt-0 last:border-0 hover:bg-surface-50"
                        @click="handleOrderClick(order)"
                    >
                        <div class="font-medium text-surface-900">
                            {{ order.orderable?.name || 'Unknown' }}
                        </div>
                        <div class="ml-4 font-semibold text-surface-900">
                            {{ formatCurrency(order.total_amount) }}
                        </div>
                    </button>
                </div>
            </div>
        </template>
    </UiCard>
</template>
