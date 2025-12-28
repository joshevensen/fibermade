import { index as basesIndex } from '@/actions/App/Http/Controllers/BaseController';
import { index as collectionsIndex } from '@/actions/App/Http/Controllers/CollectionController';
import { index as colorwaysIndex } from '@/actions/App/Http/Controllers/ColorwayController';
import { index as customersIndex } from '@/actions/App/Http/Controllers/CustomerController';
import { index as dyesIndex } from '@/actions/App/Http/Controllers/DyeController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { index as showsIndex } from '@/actions/App/Http/Controllers/ShowController';
import { useIcon } from '@/composables/useIcon';
import { dashboard } from '@/routes';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as storesIndex } from '@/routes/stores';
import type { NavItem } from '@/types';
import type { Component } from 'vue';

const { BusinessIconList } = useIcon();

interface NavigationItem extends Omit<NavItem, 'icon'> {
    icon?: string | Component;
}

export function useNavigation(): NavigationItem[] {
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
        {
            title: 'Dyes',
            href: dyesIndex.url(),
            icon: BusinessIconList.Dyes,
        },
        {
            title: 'Orders',
            href: ordersIndex.url(),
            icon: BusinessIconList.Orders,
        },
        {
            title: 'Stores',
            href: storesIndex.url(),
            icon: BusinessIconList.Stores,
        },
        {
            title: 'Shows',
            href: showsIndex.url(),
            icon: BusinessIconList.Shows,
        },
        {
            title: 'Customers',
            href: customersIndex.url(),
            icon: BusinessIconList.Customers,
        },
    ];
}
