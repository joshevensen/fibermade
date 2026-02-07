import { ref } from 'vue';

export type DrawerType =
    | 'base'
    | 'collection'
    | 'colorway'
    | 'customer'
    | 'dye'
    | 'order'
    | 'show'
    | 'store'
    | null;

// Shared state across all instances
const activeDrawer = ref<DrawerType>(null);

export function useCreateDrawer() {
    function openDrawer(type: Exclude<DrawerType, null>): void {
        activeDrawer.value = type;
    }

    function closeDrawer(): void {
        activeDrawer.value = null;
    }

    function isDrawerOpen(type: DrawerType): boolean {
        return activeDrawer.value === type;
    }

    return {
        activeDrawer,
        openDrawer,
        closeDrawer,
        isDrawerOpen,
    };
}
