<script lang="ts">
import type { DividerTokenSections } from '@primeuix/themes/types/divider';

export const dividerRoot: DividerTokenSections.Root = {
    borderColor: '{content.border.color}'
};

export const dividerContent: DividerTokenSections.Content = {
    background: '{content.background}',
    color: '{text.color}'
};

export const dividerHorizontal: DividerTokenSections.Horizontal = {
    margin: '1rem 0',
    padding: '0 1rem',
    content: {
        padding: '0 0.5rem'
    }
};

export const dividerVertical: DividerTokenSections.Vertical = {
    margin: '0 1rem',
    padding: '0.5rem 0',
    content: {
        padding: '0.5rem 0'
    }
};
</script>

<script setup lang="ts">
import PrimeDivider from 'primevue/divider';

interface Props {
    align?: 'left' | 'right' | 'top' | 'bottom' | 'center';
    layout?: 'horizontal' | 'vertical';
    type?: 'solid' | 'dashed' | 'dotted';
}

const props = withDefaults(defineProps<Props>(), {
    layout: 'horizontal',
    type: 'solid',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDivider
        v-bind="$attrs"
        :align="align"
        :layout="layout"
        :type="type"
    >
        <slot />
    </PrimeDivider>
</template>

