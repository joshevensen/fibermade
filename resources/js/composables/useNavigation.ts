import type { NavItem } from '@/types';
import { index as basesIndex } from '@/actions/App/Http/Controllers/BaseController';
import { index as collectionsIndex } from '@/actions/App/Http/Controllers/CollectionController';
import { index as colorwaysIndex } from '@/actions/App/Http/Controllers/ColorwayController';
import { index as dyesIndex } from '@/actions/App/Http/Controllers/DyeController';
import { index as discountsIndex } from '@/actions/App/Http/Controllers/DiscountController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as storesIndex } from '@/routes/stores';
import { dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/user';
import { useIcon } from '@/composables/useIcon';
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
            title: 'Stores',
            href: storesIndex.url(),
            icon: BusinessIconList.Stores,
        },
        {
            title: 'Orders',
            href: ordersIndex.url(),
            icon: BusinessIconList.Orders,
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
            title: 'Discounts',
            href: discountsIndex.url(),
            icon: BusinessIconList.Discounts,
        },
    ];
}

