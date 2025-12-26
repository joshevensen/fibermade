<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiMenu from '@/components/ui/UiMenu.vue';
import { create as createBase } from '@/actions/App/Http/Controllers/BaseController';
import { create as createCollection } from '@/actions/App/Http/Controllers/CollectionController';
import { create as createColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import { create as createDye } from '@/actions/App/Http/Controllers/DyeController';
import { create as createOrder } from '@/actions/App/Http/Controllers/OrderController';
import { edit as profileEdit } from '@/routes/profile';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const emit = defineEmits<{
    'toggle-mobile-drawer': [];
}>();

const createMenu = ref();

function toggleCreateMenu(event: Event) {
    createMenu.value?.toggle(event);
}

const createMenuItems = computed(() => [
    {
        label: 'Order',
        icon: 'pi pi-shopping-cart',
        command: () => {
            router.visit(createOrder.url());
        },
    },
    {
        label: 'Colorway',
        icon: 'pi pi-palette',
        command: () => {
            router.visit(createColorway.url());
        },
    },
    {
        label: 'Collection',
        icon: 'pi pi-folder',
        command: () => {
            router.visit(createCollection.url());
        },
    },
    {
        label: 'Dye',
        icon: 'pi pi-circle',
        command: () => {
            router.visit(createDye.url());
        },
    },
    {
        label: 'Base',
        icon: 'pi pi-box',
        command: () => {
            router.visit(createBase.url());
        },
    },
]);
</script>

<template>
    <header
        class="flex h-16 w-full items-center justify-between bg-surface-50 px-4"
    >
        <!-- Mobile: Menu toggle + Logo -->
        <div class="flex items-center gap-4 lg:hidden">
            <UiButton
                icon="pi pi-bars"
                text
                @click="emit('toggle-mobile-drawer')"
            />
            <AppLogo variant="full" />
        </div>

        <!-- Desktop: Search Input -->
        <div class="hidden lg:block lg:flex-1 lg:max-w-md">
            <UiInputText
                placeholder="Search"
                icon="pi pi-search"
                icon-pos="start"
                :fluid="false"
            />
        </div>

        <!-- Icon Buttons (all screens) -->
        <div class="flex items-center gap-2">
            <UiButton
                icon="pi pi-question-circle"
                text
                aria-label="Support"
            />
            <UiButton
                icon="pi pi-bell"
                text
                aria-label="Notifications"
            />
            <UiButton
                icon="pi pi-cog"
                text
                aria-label="Settings"
                @click="router.visit(profileEdit.url())"
            />
            <UiButton
                icon="pi pi-plus"
                text
                aria-label="Create"
                @click="toggleCreateMenu"
            />
            <UiMenu
                ref="createMenu"
                :model="createMenuItems"
                popup
            />
        </div>
    </header>
</template>
