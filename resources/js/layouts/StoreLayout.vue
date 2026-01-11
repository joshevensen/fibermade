<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import AppMobileDrawer from '@/components/AppMobileDrawer.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { useStoreNavigation } from '@/composables/useNavigation';
import { useSidebarState } from '@/composables/useSidebarState';
import type { BreadcrumbItemType } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmPopup from 'primevue/confirmpopup';
import Toast from 'primevue/toast';
import { ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {});

const navItems = useStoreNavigation();
const { collapsed: sidebarCollapsed } = useSidebarState();
const mobileDrawerVisible = ref(false);
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
                hide-create-buttons
                @toggle-mobile-drawer="
                    mobileDrawerVisible = !mobileDrawerVisible
                "
            />

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

    <!-- Global Components -->
    <ConfirmPopup />
    <Toast />
</template>
