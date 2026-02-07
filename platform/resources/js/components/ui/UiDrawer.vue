<script lang="ts">
import type { DrawerTokenSections } from '@primeuix/themes/types/drawer';

export const drawerRoot: DrawerTokenSections.Root = {
    background: '{overlay.modal.background}',
    borderColor: '{overlay.modal.border.color}',
    color: '{overlay.modal.color}',
    shadow: '{overlay.modal.shadow}'
};

export const drawerHeader: DrawerTokenSections.Header = {
    padding: '{overlay.modal.padding}'
};

export const drawerTitle: DrawerTokenSections.Title = {
    fontSize: '1.5rem',
    fontWeight: '600'
};

export const drawerContent: DrawerTokenSections.Content = {
    padding: '0 {overlay.modal.padding} {overlay.modal.padding} {overlay.modal.padding}'
};

export const drawerFooter: DrawerTokenSections.Footer = {
    padding: '{overlay.modal.padding}'
};
</script>

<script setup lang="ts">
import PrimeDrawer from 'primevue/drawer';
import { useSlots } from 'vue';

interface Props {
    visible?: boolean;
    position?: 'left' | 'right' | 'top' | 'bottom' | 'full';
    header?: string;
    dismissable?: boolean;
    showCloseIcon?: boolean;
    modal?: boolean;
    blockScroll?: boolean;
    closeOnEscape?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
    position: 'left',
    dismissable: true,
    showCloseIcon: true,
    modal: true,
    closeOnEscape: true,
});

defineOptions({
    inheritAttrs: false,
});

const slots = useSlots();
const hasFooter = !!slots.footer;
</script>

<template>
    <PrimeDrawer
        v-bind="$attrs"
        :visible="visible"
        :position="position"
        :header="header"
        :dismissable="dismissable"
        :showCloseIcon="showCloseIcon"
        :modal="modal"
        :blockScroll="blockScroll"
        :closeOnEscape="closeOnEscape"
    >
        <template #header>
            <slot name="header" />
        </template>
        <template v-if="hasFooter" #footer>
            <slot name="footer" />
        </template>
        <slot />
    </PrimeDrawer>
</template>

