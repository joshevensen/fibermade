<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';
import AppHeader from '@/components/AppHeader.vue';
import AppMobileDrawer from '@/components/AppMobileDrawer.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { useNavigation } from '@/composables/useNavigation';
import { useSidebarState } from '@/composables/useSidebarState';
import { Head } from '@inertiajs/vue3';
import ConfirmPopup from 'primevue/confirmpopup';
import Toast from 'primevue/toast';
import { ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {});

const navItems = useNavigation();
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
            <AppHeader @toggle-mobile-drawer="mobileDrawerVisible = !mobileDrawerVisible" />

            <!-- Page Content -->
            <main class="flex-1 overflow-auto pt-3.5 px-4 pb-8">
                <slot />
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
