<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';

interface Props {
    revenueThisMonth: number;
}

const props = defineProps<Props>();

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}
</script>

<template>
    <UiCard>
        <template #title> Revenue This Month </template>
        <template #content>
            <div class="text-3xl font-semibold text-surface-900">
                {{ formatCurrency(revenueThisMonth) }}
            </div>
            <div class="mt-1 text-sm text-surface-600">
                Total revenue from orders
            </div>
        </template>
    </UiCard>
</template>
