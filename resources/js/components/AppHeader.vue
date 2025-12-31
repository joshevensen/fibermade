<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiIcon from '@/components/ui/UiIcon.vue';
import UiMenu from '@/components/ui/UiMenu.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { edit as profileEdit } from '@/routes/user';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    pageTitle?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'toggle-mobile-drawer': [];
}>();

const { openDrawer } = useCreateDrawer();
const { IconList, BusinessIconList } = useIcon();
const createMenuRef = ref();

// Map page titles to icons
const getPageIcon = (title?: string) => {
    if (!title) {
        return null;
    }
    const iconMap: Record<string, any> = {
        Colorways: BusinessIconList.Colorways,
        Orders: BusinessIconList.Orders,
        Dashboard: BusinessIconList.Dashboard,
        Inventory: BusinessIconList.Inventory,
        Collections: BusinessIconList.Collections,
        Bases: BusinessIconList.Bases,
        Dyes: BusinessIconList.Dyes,
        Stores: BusinessIconList.Stores,
        Shows: BusinessIconList.Shows,
        Customers: BusinessIconList.Customers,
    };
    return iconMap[title] || null;
};

const pageIcon = computed(() => getPageIcon(props.pageTitle));

const createMenuItems = [
    {
        label: 'Base',
        icon: IconList.Plus,
        command: () => {
            openDrawer('base');
        },
    },
    {
        label: 'Collection',
        icon: IconList.Plus,
        command: () => {
            openDrawer('collection');
        },
    },
    {
        label: 'Colorway',
        icon: IconList.Plus,
        command: () => {
            openDrawer('colorway');
        },
    },
    {
        label: 'Customer',
        icon: IconList.Plus,
        command: () => {
            openDrawer('customer');
        },
    },
    {
        label: 'Discount',
        icon: IconList.Plus,
        command: () => {
            openDrawer('discount');
        },
    },
    {
        label: 'Dye',
        icon: IconList.Plus,
        command: () => {
            openDrawer('dye');
        },
    },
    {
        label: 'Order',
        icon: IconList.Plus,
        command: () => {
            openDrawer('order');
        },
    },
    {
        label: 'Show',
        icon: IconList.Plus,
        command: () => {
            openDrawer('show');
        },
    },
    {
        label: 'Store',
        icon: IconList.Plus,
        command: () => {
            openDrawer('store');
        },
    },
];

function toggleCreateMenu(event: Event): void {
    createMenuRef.value?.toggle(event);
}
</script>

<template>
    <header
        class="flex h-12 w-full items-center justify-between bg-surface-100 px-4"
    >
        <!-- Mobile: Menu toggle + Logo -->
        <div class="flex items-center gap-4 lg:hidden">
            <UiButton
                :icon="IconList.Menu"
                text
                @click="emit('toggle-mobile-drawer')"
            />
            <AppLogo variant="full" class="max-w-32" />
        </div>

        <!-- Desktop: Page Title -->
        <div v-if="pageTitle" class="hidden items-center gap-2 lg:flex">
            <UiIcon
                v-if="pageIcon"
                :component="pageIcon"
                class="text-lg text-surface-400"
            />
            <h1 class="text-xl font-bold text-surface-500">{{ pageTitle }}</h1>
        </div>

        <!-- Icon Buttons (all screens) -->
        <div class="relative ml-auto flex items-center gap-2">
            <UiButton
                :icon="IconList.Settings"
                text
                aria-label="Settings"
                @click="router.visit(profileEdit.url())"
            />
            <UiButton
                :icon="IconList.Plus"
                text
                aria-label="Create"
                @click="toggleCreateMenu"
            />
            <UiMenu
                ref="createMenuRef"
                :model="createMenuItems"
                popup
                append-to="body"
            />
        </div>
    </header>
</template>
