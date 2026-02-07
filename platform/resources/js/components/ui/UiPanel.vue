<script lang="ts">
import type { PanelTokenSections } from '@primeuix/themes/types/panel';

export const panelRoot: PanelTokenSections.Root = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}'
};

export const panelHeader: PanelTokenSections.Header = {
    background: 'transparent',
    color: '{text.color}',
    padding: '1.125rem',
    borderColor: '{content.border.color}',
    borderWidth: '0',
    borderRadius: '0'
};

export const panelToggleableHeader: PanelTokenSections.ToggleableHeader = {
    padding: '0.375rem 1.125rem'
};

export const panelTitle: PanelTokenSections.Title = {
    fontWeight: '600'
};

export const panelContent: PanelTokenSections.Content = {
    padding: '0 1.125rem 1.125rem 1.125rem'
};

export const panelFooter: PanelTokenSections.Footer = {
    padding: '0 1.125rem 1.125rem 1.125rem'
};
</script>

<script setup lang="ts">
import PrimePanel from 'primevue/panel';

interface Props {
    header?: string;
    toggleable?: boolean;
    collapsed?: boolean;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimePanel
        v-bind="$attrs"
        :header="header"
        :toggleable="toggleable"
        :collapsed="collapsed"
    >
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </PrimePanel>
</template>

