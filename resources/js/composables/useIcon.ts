export const IconList = {
    ActionMenu: 'pi pi-ellipsis-h',
    Check: 'pi pi-check',
    Circle: 'pi pi-circle',
    Close: 'pi pi-times',
    DarkMode: 'pi pi-moon',
    Down: 'pi pi-chevron-down',
    LightMode: 'pi pi-sun',
    Menu: 'pi pi-bars',
    Minus: 'pi pi-minus',
    Right: 'pi pi-chevron-right',
    Spinner: 'pi pi-spinner',
    SystemMode: 'pi pi-desktop',
} as const;

export function useIcon() {
    return {
        IconList,
    };
}
