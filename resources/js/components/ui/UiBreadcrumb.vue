<script lang="ts">
import type { BreadcrumbTokenSections } from '@primeuix/themes/types/breadcrumb';

export const breadcrumbRoot: BreadcrumbTokenSections.Root = {
    padding: '1rem',
    background: '{content.background}',
    gap: '0.5rem',
    transitionDuration: '{transition.duration}'
};

export const breadcrumbItem: BreadcrumbTokenSections.Item = {
    color: '{text.muted.color}',
    hoverColor: '{text.color}',
    borderRadius: '{content.border.radius}',
    gap: '{navigation.item.gap}',
    icon: {
        color: '{navigation.item.icon.color}',
        hoverColor: '{navigation.item.icon.focus.color}'
    },
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};

export const breadcrumbSeparator: BreadcrumbTokenSections.Separator = {
    color: '{navigation.item.icon.color}'
};
</script>

<script setup lang="ts">
import PrimeBreadcrumb from 'primevue/breadcrumb';

interface Props {
    model?: any[];
    home?: any;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeBreadcrumb v-bind="$attrs" :model="model" :home="home">
        <slot />
    </PrimeBreadcrumb>
</template>

