import { index as basesIndex } from '@/actions/App/Http/Controllers/BaseController';
import { index as collectionsIndex } from '@/actions/App/Http/Controllers/CollectionController';
import { index as colorwaysIndex } from '@/actions/App/Http/Controllers/ColorwayController';
import { index as inventoryIndex } from '@/actions/App/Http/Controllers/InventoryController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
// import { index as showsIndex } from '@/actions/App/Http/Controllers/ShowController';
import { useIcon } from '@/composables/useIcon';
import { dashboard } from '@/routes';
import { index as storesIndex } from '@/routes/stores';
import { edit as userEdit } from '@/routes/user';
import type { NavItem } from '@/types';
import type { Component } from 'vue';

const { BusinessIconList } = useIcon();

interface NavigationItem extends Omit<NavItem, 'icon'> {
    icon?: string | Component;
}

export function useCreatorNavigation(): NavigationItem[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard.url(),
            icon: BusinessIconList.Dashboard,
        },
        {
            title: 'Inventory',
            href: inventoryIndex.url(),
            icon: BusinessIconList.Inventory,
        },
        {
            title: 'Colorways',
            href: colorwaysIndex.url(),
            icon: BusinessIconList.Colorways,
        },
        {
            title: 'Collections',
            href: collectionsIndex.url(),
            icon: BusinessIconList.Collections,
        },
        {
            title: 'Bases',
            href: basesIndex.url(),
            icon: BusinessIconList.Bases,
        },
        // {
        //     title: 'Shows',
        //     href: showsIndex.url(),
        //     icon: BusinessIconList.Shows,
        // },
        {
            title: 'Stores',
            href: storesIndex.url(),
            icon: BusinessIconList.Stores,
        },
        // {
        //     title: 'Customers',
        //     href: customersIndex.url(),
        //     icon: BusinessIconList.Customers,
        // },
        {
            title: 'Orders',
            href: ordersIndex.url(),
            icon: BusinessIconList.Orders,
        },
        {
            title: 'Settings',
            href: userEdit.url(),
            icon: BusinessIconList.Settings,
        },
    ];
}

export function useStoreNavigation(): NavigationItem[] {
    return [
        {
            title: 'Home',
            href: '/store',
            icon: BusinessIconList.Dashboard,
        },
        {
            title: 'Settings',
            href: '/store/settings',
            icon: BusinessIconList.Settings,
        },
    ];
}

// Backwards compatibility - export as default for existing code
export function useNavigation(): NavigationItem[] {
    return useCreatorNavigation();
}
