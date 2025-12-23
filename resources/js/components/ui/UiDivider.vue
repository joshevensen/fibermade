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

