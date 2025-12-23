<script setup lang="ts">
import PrimeMenu from 'primevue/menu';

interface Props {
    model?: any[];
    popup?: boolean;
    appendTo?: HTMLElement | 'body' | 'self';
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMenu v-bind="$attrs" :model="model" :popup="popup" :appendTo="appendTo">
        <slot />
    </PrimeMenu>
</template>

