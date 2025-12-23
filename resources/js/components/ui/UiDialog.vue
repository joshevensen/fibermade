<script setup lang="ts">
import PrimeDialog from 'primevue/dialog';

interface Props {
    visible?: boolean;
    modal?: boolean;
    header?: string;
    footer?: string;
    closable?: boolean;
    dismissableMask?: boolean;
    closeOnEscape?: boolean;
    showHeader?: boolean;
    blockScroll?: boolean;
    position?: 'left' | 'right' | 'top' | 'bottom' | 'center' | 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    maximizable?: boolean;
    draggable?: boolean;
    appendTo?: HTMLElement | 'body' | 'self';
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
    modal: false,
    closable: true,
    closeOnEscape: true,
    showHeader: true,
    position: 'center',
    draggable: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDialog
        v-bind="$attrs"
        :visible="visible"
        :modal="modal"
        :header="header"
        :footer="footer"
        :closable="closable"
        :dismissableMask="dismissableMask"
        :closeOnEscape="closeOnEscape"
        :showHeader="showHeader"
        :blockScroll="blockScroll"
        :position="position"
        :maximizable="maximizable"
        :draggable="draggable"
        :appendTo="appendTo"
    >
        <template #header>
            <slot name="header" />
        </template>
        <template #footer>
            <slot name="footer" />
        </template>
        <slot />
    </PrimeDialog>
</template>

