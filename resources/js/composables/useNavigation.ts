import type { NavItem } from '@/types';
import { index as basesIndex } from '@/actions/App/Http/Controllers/BaseController';
import { index as collectionsIndex } from '@/actions/App/Http/Controllers/CollectionController';
import { index as colorwaysIndex } from '@/actions/App/Http/Controllers/ColorwayController';
import { index as dyesIndex } from '@/actions/App/Http/Controllers/DyeController';
import { index as discountsIndex } from '@/actions/App/Http/Controllers/DiscountController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { index as inventoryIndex } from '@/routes/inventory';
import { dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';

interface NavigationItem extends Omit<NavItem, 'icon'> {
    icon?: string;
}

export function useNavigation(): NavigationItem[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard.url(),
            icon: 'pi pi-home',
        },
        {
            title: 'Orders',
            href: ordersIndex.url(),
            icon: 'pi pi-shopping-cart',
        },
        {
            title: 'Inventory',
            href: inventoryIndex.url(),
            icon: 'pi pi-th-large',
        },
        {
            title: 'Colorways',
            href: colorwaysIndex.url(),
            icon: 'pi pi-palette',
        },
        {
            title: 'Collections',
            href: collectionsIndex.url(),
            icon: 'pi pi-folder',
        },
        {
            title: 'Bases',
            href: basesIndex.url(),
            icon: 'pi pi-box',
        },
        {
            title: 'Dyes',
            href: dyesIndex.url(),
            icon: 'pi pi-circle',
        },
        {
            title: 'Discounts',
            href: discountsIndex.url(),
            icon: 'pi pi-tag',
        },
    ];
}

