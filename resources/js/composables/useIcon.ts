// UI Icons - Prime Icons (CSS classes)
export const IconList = {
    ActionMenu: 'pi pi-ellipsis-h',
    Check: 'pi pi-check',
    Circle: 'pi pi-circle',
    Close: 'pi pi-times',
    DarkMode: 'pi pi-moon',
    Down: 'pi pi-chevron-down',
    ExclamationTriangle: 'pi pi-exclamation-triangle',
    Left: 'pi pi-chevron-left',
    LightMode: 'pi pi-sun',
    Menu: 'pi pi-bars',
    Minus: 'pi pi-minus',
    Plus: 'pi pi-plus',
    Right: 'pi pi-chevron-right',
    Search: 'pi pi-search',
    Settings: 'pi pi-cog',
    SignOut: 'pi pi-sign-out',
    Spinner: 'pi pi-spinner',
    SystemMode: 'pi pi-desktop',
} as const;

// Business Icons - Tabler Icons (Vue components)
import {
    IconBox,
    IconCalendarEvent,
    IconDashboard,
    IconDroplet,
    IconFolder,
    IconLayoutGrid,
    IconPalette,
    IconSettings,
    IconShoppingBag,
    IconShoppingCart,
    IconUsers,
} from '@tabler/icons-vue';

export const BusinessIconList = {
    Bases: IconBox,
    Collections: IconFolder,
    Colorways: IconPalette,
    Customers: IconUsers,
    Dyes: IconDroplet,
    Orders: IconShoppingCart,
    Stores: IconShoppingBag,
    Inventory: IconLayoutGrid,
    Dashboard: IconDashboard,
    Shows: IconCalendarEvent,
    Settings: IconSettings,
} as const;

export function useIcon() {
    return {
        IconList,
        BusinessIconList,
    };
}
