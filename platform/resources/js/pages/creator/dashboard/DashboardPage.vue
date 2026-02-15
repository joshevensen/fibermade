<script setup lang="ts">
import { useIcon } from '@/composables/useIcon';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import NeedsAttentionCard from './components/NeedsAttentionCard.vue';
import StatsCard from './components/StatsCard.vue';
import WholesaleOrdersSummaryCard from './components/WholesaleOrdersSummaryCard.vue';

interface Orderable {
    name: string;
}

interface Order {
    id: number;
    order_date: string;
    total_amount?: number | null;
    orderable?: Orderable | null;
}

interface NeedsAttention {
    pending_orders: number;
    pending_store_invites: number;
}

interface Props {
    colorwayCount: number;
    collectionCount: number;
    storeCount: number;
    /** Orders grouped by status: { open: Order[], accepted: Order[], ... } */
    activeOrders: Record<string, Order[]>;
    needsAttention: NeedsAttention;
}

defineProps<Props>();

const { BusinessIconList } = useIcon();
</script>

<template>
    <CreatorLayout page-title="Dashboard">
        <div class="flex flex-col gap-6">
            <!-- Stats row: horizontal on desktop, stack on mobile -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatsCard
                    label="Colorways"
                    :count="colorwayCount"
                    href="/creator/colorways"
                    :icon="BusinessIconList.Colorways"
                />
                <StatsCard
                    label="Collections"
                    :count="collectionCount"
                    href="/creator/collections"
                    :icon="BusinessIconList.Collections"
                />
                <StatsCard
                    label="Stores"
                    :count="storeCount"
                    href="/creator/stores"
                    :icon="BusinessIconList.Stores"
                />
            </div>

            <!-- Two-column: main (orders) + sidebar (needs attention) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <WholesaleOrdersSummaryCard :active-orders="activeOrders" />
                </div>
                <div class="order-first lg:order-none">
                    <NeedsAttentionCard :needs-attention="needsAttention" />
                </div>
            </div>
        </div>
    </CreatorLayout>
</template>
