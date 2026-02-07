<script setup lang="ts">
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import DyeListCard from './components/DyeListCard.vue';
import OpenOrdersCard from './components/OpenOrdersCard.vue';
import RevenueThisMonthCard from './components/RevenueThisMonthCard.vue';
// import UpcomingShowsCard from './components/UpcomingShowsCard.vue';

interface Colorway {
    id: number;
    name: string;
    per_pan: number;
}

interface Base {
    id: number;
    descriptor: string;
}

interface DyeListItem {
    colorway: Colorway;
    base: Base;
    quantity: number;
}

interface Show {
    id: number;
    name: string;
    start_at: string;
    end_at: string;
    location_name?: string | null;
    location_city?: string | null;
    location_state?: string | null;
}

interface Order {
    id: number;
    total_amount?: number | null;
    orderable?: {
        name: string;
    } | null;
}

interface Props {
    dyeList: DyeListItem[];
    upcomingShows: Show[];
    openOrders: Order[];
    revenueThisMonth: number;
}

const props = defineProps<Props>();
</script>

<template>
    <CreatorLayout page-title="Dashboard">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column: Dye List (spans 2 columns on desktop) -->
            <div class="order-1 lg:col-span-2">
                <DyeListCard :dye-list="dyeList" />
            </div>

            <!-- Right Column: Cards (spans 1 column on desktop) -->
            <div class="order-2 space-y-6">
                <!-- <UpcomingShowsCard :upcoming-shows="upcomingShows" /> -->
                <RevenueThisMonthCard :revenue-this-month="revenueThisMonth" />
                <OpenOrdersCard :open-orders="openOrders" />
            </div>
        </div>
    </CreatorLayout>
</template>
