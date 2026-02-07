<script lang="ts">
import type { MenuTokenSections } from '@primeuix/themes/types/menu';

export const menuRoot: MenuTokenSections.Root = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}',
    shadow: '{overlay.navigation.shadow}',
    transitionDuration: '{transition.duration}'
};

export const menuList: MenuTokenSections.List = {
    padding: '{navigation.list.padding}',
    gap: '{navigation.list.gap}'
};

export const menuItem: MenuTokenSections.Item = {
    focusBackground: '{navigation.item.focus.background}',
    color: '{navigation.item.color}',
    focusColor: '{navigation.item.focus.color}',
    padding: '{navigation.item.padding}',
    borderRadius: '{navigation.item.border.radius}',
    gap: '{navigation.item.gap}',
    icon: {
        color: '{navigation.item.icon.color}',
        focusColor: '{navigation.item.icon.focus.color}'
    }
};

export const menuSubmenuLabel: MenuTokenSections.SubmenuLabel = {
    padding: '{navigation.submenu.label.padding}',
    fontWeight: '{navigation.submenu.label.font.weight}',
    background: '{navigation.submenu.label.background}',
    color: '{navigation.submenu.label.color}'
};

export const menuSeparator: MenuTokenSections.Separator = {
    borderColor: '{content.border.color}'
};
</script>

<script setup lang="ts">
import PrimeMenu from 'primevue/menu';
import { ref } from 'vue';

interface Props {
    model?: any[];
    popup?: boolean;
    appendTo?: HTMLElement | 'body' | 'self';
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});

const menuRef = ref<InstanceType<typeof PrimeMenu>>();

function toggle(event: Event): void {
    menuRef.value?.toggle(event);
}

defineExpose({
    toggle,
});
</script>

<template>
    <PrimeMenu
        ref="menuRef"
        v-bind="$attrs"
        :model="model"
        :popup="popup"
        :appendTo="appendTo"
    >
        <slot />
    </PrimeMenu>
</template>

