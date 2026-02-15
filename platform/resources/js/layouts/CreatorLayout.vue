<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import AppMobileDrawer from '@/components/AppMobileDrawer.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import PaymentFailedBanner from '@/pages/creator/components/PaymentFailedBanner.vue';
import ReactivationBanner from '@/pages/creator/components/ReactivationBanner.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useCreatorNavigation } from '@/composables/useNavigation';
import { useSidebarState } from '@/composables/useSidebarState';
import BaseCreateDrawer from '@/pages/creator/bases/BaseCreateDrawer.vue';
import CollectionCreateDrawer from '@/pages/creator/collections/CollectionCreateDrawer.vue';
import ColorwayCreateDrawer from '@/pages/creator/colorways/ColorwayCreateDrawer.vue';
// TODO: Re-enable CustomerCreateDrawer in Stage 2
// import CustomerCreateDrawer from '@/pages/creator/customers/CustomerCreateDrawer.vue';
// TODO: Re-enable OrderCreateDrawer when ready to work on orders
// import OrderCreateDrawer from '@/pages/creator/orders/OrderCreateDrawer.vue';
import ShowCreateDrawer from '@/pages/creator/shows/ShowCreateDrawer.vue';
import StoreCreateDrawer from '@/pages/creator/stores/StoreCreateDrawer.vue';
import type { BreadcrumbItemType } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmPopup from 'primevue/confirmpopup';
import Toast from 'primevue/toast';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {});

const navItems = useCreatorNavigation();
const { collapsed: sidebarCollapsed } = useSidebarState();
const mobileDrawerVisible = ref(false);

const { activeDrawer, closeDrawer } = useCreateDrawer();

const baseDrawerVisible = computed(() => activeDrawer.value === 'base');
const collectionDrawerVisible = computed(
    () => activeDrawer.value === 'collection',
);
const colorwayDrawerVisible = computed(() => activeDrawer.value === 'colorway');
// TODO: Re-enable CustomerCreateDrawer in Stage 2
// const customerDrawerVisible = computed(() => activeDrawer.value === 'customer');
// TODO: Re-enable OrderCreateDrawer when ready to work on orders
// const orderDrawerVisible = computed(() => activeDrawer.value === 'order');
const showDrawerVisible = computed(() => activeDrawer.value === 'show');
const storeDrawerVisible = computed(() => activeDrawer.value === 'store');
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />

    <div class="flex h-screen overflow-hidden">
        <!-- Desktop Sidebar -->
        <AppSidebar
            :collapsed="sidebarCollapsed"
            :nav-items="navItems"
            @update:collapsed="sidebarCollapsed = $event"
        />

        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <AppHeader
                :page-title="pageTitle"
                @toggle-mobile-drawer="
                    mobileDrawerVisible = !mobileDrawerVisible
                "
            />

            <PaymentFailedBanner />
            <ReactivationBanner />

            <!-- Page Content -->
            <main class="flex-1 overflow-auto px-4 pt-3.5 pb-8">
                <template v-if="$slots.side">
                    <div class="flex flex-col gap-4 lg:flex-row lg:pr-4">
                        <div class="flex-[0_0_60%]">
                            <slot />
                        </div>
                        <div class="flex-[0_0_40%]">
                            <slot name="side" />
                        </div>
                    </div>
                </template>
                <template v-else>
                    <slot />
                </template>
            </main>
        </div>

        <!-- Mobile Drawer -->
        <AppMobileDrawer
            v-model:visible="mobileDrawerVisible"
            :nav-items="navItems"
        />
    </div>

    <!-- Create Drawers -->
    <BaseCreateDrawer
        :visible="baseDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    />
    <CollectionCreateDrawer
        :visible="collectionDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    />
    <ColorwayCreateDrawer
        :visible="colorwayDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    />
    <!-- TODO: Re-enable CustomerCreateDrawer in Stage 2 -->
    <!-- <CustomerCreateDrawer
        :visible="customerDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    /> -->
    <!-- TODO: Re-enable OrderCreateDrawer when ready to work on orders -->
    <!-- <OrderCreateDrawer
        :visible="orderDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    /> -->
    <ShowCreateDrawer
        :visible="showDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    />
    <StoreCreateDrawer
        :visible="storeDrawerVisible"
        @update:visible="
            (value) => {
                if (!value) closeDrawer();
            }
        "
    />

    <!-- Global Components -->
    <ConfirmPopup />
    <Toast />
</template>
