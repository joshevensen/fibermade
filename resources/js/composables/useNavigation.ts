import type { NavItem } from '@/types';
import { index as basesIndex } from '@/actions/App/Http/Controllers/BaseController';
import { index as collectionsIndex } from '@/actions/App/Http/Controllers/CollectionController';
import { index as colorwaysIndex } from '@/actions/App/Http/Controllers/ColorwayController';
import { index as dyesIndex } from '@/actions/App/Http/Controllers/DyeController';
import { index as discountsIndex } from '@/actions/App/Http/Controllers/DiscountController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { index as inventoryIndex } from '@/routes/inventory';
import { dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/user';
import { useIcon } from '@/composables/useIcon';

const { IconList } = useIcon();

interface NavigationItem extends Omit<NavItem, 'icon'> {
    icon?: string;
}

export function useNavigation(): NavigationItem[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard.url(),
            icon: IconList.Dashboard,
        },
        {
            title: 'Inventory',
            href: inventoryIndex.url(),
            icon: IconList.Inventory,
        },
        {
            title: 'Orders',
            href: ordersIndex.url(),
            icon: IconList.Orders,
        },
        {
            title: 'Colorways',
            href: colorwaysIndex.url(),
            icon: IconList.Colorways,
        },
        {
            title: 'Collections',
            href: collectionsIndex.url(),
            icon: IconList.Collections,
        },
        {
            title: 'Bases',
            href: basesIndex.url(),
            icon: IconList.Bases,
        },
        {
            title: 'Dyes',
            href: dyesIndex.url(),
            icon: IconList.Dyes,
        },
        {
            title: 'Discounts',
            href: discountsIndex.url(),
            icon: IconList.Discounts,
        },
    ];
}

