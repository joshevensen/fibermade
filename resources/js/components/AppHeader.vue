<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiMenu from '@/components/ui/UiMenu.vue';
import { edit as profileEdit } from '@/routes/user';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const emit = defineEmits<{
    'toggle-mobile-drawer': [];
}>();

const { openDrawer } = useCreateDrawer();
const { IconList } = useIcon();
const createMenuRef = ref();

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
            <AppLogo variant="full" />
        </div>

        <!-- Desktop: Search Input -->
        <div class="hidden lg:flex gap-3 lg:flex-1 lg:max-w-md">
            <UiInputText
                placeholder="Search"
                :icon="IconList.Search"
                icon-pos="start"
                :fluid="false"
            />

            <UiButton
                label="Dye List"
                outlined
            />
        </div>

        <!-- Icon Buttons (all screens) -->
        <div class="relative flex items-center gap-2">
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
