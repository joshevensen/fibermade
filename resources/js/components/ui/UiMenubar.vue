<script lang="ts">
import type { MenubarTokenSections } from '@primeuix/themes/types/menubar';

export const menubarRoot: MenubarTokenSections.Root = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    borderRadius: '{content.border.radius}',
    color: '{content.color}',
    gap: '0.5rem',
    padding: '0.5rem 0.75rem',
    transitionDuration: '{transition.duration}'
};

export const menubarBaseItem: MenubarTokenSections.BaseItem = {
    borderRadius: '{content.border.radius}',
    padding: '{navigation.item.padding}'
};

export const menubarItem: MenubarTokenSections.Item = {
    focusBackground: '{navigation.item.focus.background}',
    activeBackground: '{navigation.item.active.background}',
    color: '{navigation.item.color}',
    focusColor: '{navigation.item.focus.color}',
    activeColor: '{navigation.item.active.color}',
    padding: '{navigation.item.padding}',
    borderRadius: '{navigation.item.border.radius}',
    gap: '{navigation.item.gap}',
    icon: {
        color: '{navigation.item.icon.color}',
        focusColor: '{navigation.item.icon.focus.color}',
        activeColor: '{navigation.item.icon.active.color}'
    }
};

export const menubarSubmenu: MenubarTokenSections.Submenu = {
    padding: '{navigation.list.padding}',
    gap: '{navigation.list.gap}',
    background: '{content.background}',
    borderColor: '{content.border.color}',
    borderRadius: '{content.border.radius}',
    shadow: '{overlay.navigation.shadow}',
    mobileIndent: '1rem',
    icon: {
        size: '{navigation.submenu.icon.size}',
        color: '{navigation.submenu.icon.color}',
        focusColor: '{navigation.submenu.icon.focus.color}',
        activeColor: '{navigation.submenu.icon.active.color}'
    }
};

export const menubarSeparator: MenubarTokenSections.Separator = {
    borderColor: '{content.border.color}'
};

export const menubarMobileButton: MenubarTokenSections.MobileButton = {
    borderRadius: '50%',
    size: '1.75rem',
    color: '{text.muted.color}',
    hoverColor: '{text.hover.muted.color}',
    hoverBackground: '{content.hover.background}',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};
</script>

<script setup lang="ts">
import PrimeMenubar from 'primevue/menubar';

interface MenuItem {
    label?: string;
    icon?: string;
    command?: () => void;
    url?: string;
    items?: MenuItem[];
    disabled?: boolean;
    separator?: boolean;
    badge?: string;
    shortcut?: string;
    target?: string;
    [key: string]: any;
}

interface Props {
    model?: MenuItem[];
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMenubar v-bind="$attrs" :model="model">
        <template #start>
            <slot name="start" />
        </template>
        <template #item="slotProps">
            <slot name="item" v-bind="slotProps" />
        </template>
        <template #end>
            <slot name="end" />
        </template>
    </PrimeMenubar>
</template>

