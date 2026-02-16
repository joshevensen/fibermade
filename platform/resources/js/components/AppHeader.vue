<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiIcon from '@/components/ui/UiIcon.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { computed } from 'vue';

interface Props {
    pageTitle?: string;
    hideCreateButtons?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'toggle-mobile-drawer': [];
}>();

const { openDrawer } = useCreateDrawer();
const { IconList, BusinessIconList } = useIcon();

// Map page titles to drawer types
// TODO: Re-enable customer creation in Stage 2
// TODO: Re-enable order creation when ready to work on orders
const pageTitleToDrawerType: Record<
    string,
    'base' | 'collection' | 'colorway' | 'customer' | 'order' | 'show' | 'store'
> = {
    Bases: 'base',
    Collections: 'collection',
    Colorways: 'colorway',
    // Customers: 'customer', // Disabled in Stage 1
    // Orders: 'order', // Disabled - ready to work on orders
    Shows: 'show',
    Stores: 'store',
};

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
        Stores: BusinessIconList.Stores,
        Shows: BusinessIconList.Shows,
        Customers: BusinessIconList.Customers,
    };
    return iconMap[title] || null;
};

const pageIcon = computed(() => getPageIcon(props.pageTitle));

// Check if current page has a create drawer
const currentPageDrawerType = computed(() => {
    if (!props.pageTitle) {
        return null;
    }
    return pageTitleToDrawerType[props.pageTitle] || null;
});

const hasCreateDrawer = computed(() => {
    if (props.hideCreateButtons) {
        return false;
    }
    return currentPageDrawerType.value !== null;
});

// Get singular form of page title for button label
const createButtonLabel = computed(() => {
    if (!props.pageTitle || !hasCreateDrawer.value) {
        return undefined;
    }
    if (props.pageTitle === 'Stores') {
        return 'Invite Store';
    }
    // Remove 's' from plural (Bases -> Base, Colorways -> Colorway, etc.)
    return `Create ${props.pageTitle.slice(0, -1)}`;
});

function handleCreateClick(): void {
    if (currentPageDrawerType.value) {
        openDrawer(currentPageDrawerType.value);
    }
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
        <div
            v-if="!hideCreateButtons"
            class="relative ml-auto flex items-center gap-2"
        >
            <slot name="actions">
                <UiButton
                    v-if="hasCreateDrawer"
                    :icon="IconList.Plus"
                    size="small"
                    :label="createButtonLabel"
                    aria-label="Create"
                    @click="handleCreateClick"
                />
            </slot>
        </div>
    </header>
</template>
