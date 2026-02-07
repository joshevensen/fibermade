<script lang="ts">
import type { DialogTokenSections } from '@primeuix/themes/types/dialog';

export const dialogRoot: DialogTokenSections.Root = {
    background: '{overlay.modal.background}',
    borderColor: '{overlay.modal.border.color}',
    color: '{overlay.modal.color}',
    borderRadius: '{overlay.modal.border.radius}',
    shadow: '{overlay.modal.shadow}'
};

export const dialogHeader: DialogTokenSections.Header = {
    padding: '{overlay.modal.padding}',
    gap: '0.5rem'
};

export const dialogTitle: DialogTokenSections.Title = {
    fontSize: '1.25rem',
    fontWeight: '600'
};

export const dialogContent: DialogTokenSections.Content = {
    padding: '0 {overlay.modal.padding} {overlay.modal.padding} {overlay.modal.padding}'
};

export const dialogFooter: DialogTokenSections.Footer = {
    padding: '0 {overlay.modal.padding} {overlay.modal.padding} {overlay.modal.padding}',
    gap: '0.5rem'
};
</script>

<script setup lang="ts">
import PrimeDialog from 'primevue/dialog';
import { computed } from 'vue';

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
    size?: 'small' | 'medium' | 'large';
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
    modal: false,
    closable: true,
    closeOnEscape: true,
    showHeader: true,
    position: 'center',
    draggable: true,
    size: 'medium',
});

const dialogStyle = computed(() => {
    const widthMap = {
        small: '28rem', // 448px 
        medium: '32rem', // 512px 
    };

    if (props.size === 'large') {
        return {
            width: '90vw',
            maxWidth: '90vw',
        };
    }

    return {
        width: widthMap[props.size],
        maxWidth: '90vw',
    };
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
        :style="dialogStyle"
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

