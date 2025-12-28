export const IconList = {
    ActionMenu: 'pi pi-ellipsis-h',
    Bases: 'pi pi-box',
    Check: 'pi pi-check',
    Circle: 'pi pi-circle',
    Close: 'pi pi-times',
    Collections: 'pi pi-folder',
    Colorways: 'pi pi-palette',
    DarkMode: 'pi pi-moon',
    Dashboard: 'pi pi-home',
    Discounts: 'pi pi-tag',
    Down: 'pi pi-chevron-down',
    Dyes: 'pi pi-circle',
    ExclamationTriangle: 'pi pi-exclamation-triangle',
    Inventory: 'pi pi-th-large',
    Left: 'pi pi-chevron-left',
    LightMode: 'pi pi-sun',
    Menu: 'pi pi-bars',
    Minus: 'pi pi-minus',
    Orders: 'pi pi-shopping-cart',
    Plus: 'pi pi-plus',
    Right: 'pi pi-chevron-right',
    Search: 'pi pi-search',
    Settings: 'pi pi-cog',
    SignOut: 'pi pi-sign-out',
    Spinner: 'pi pi-spinner',
    Stores: 'pi pi-shopping-bag',
    SystemMode: 'pi pi-desktop',
} as const;

export function useIcon() {
    return {
        IconList,
    };
}
