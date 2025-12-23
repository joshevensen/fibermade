<script setup lang="ts">
import PrimeProgressSpinner from 'primevue/progressspinner';

interface Props {
    strokeWidth?: string;
    fill?: string;
    animationDuration?: string;
}

const props = withDefaults(defineProps<Props>(), {
    strokeWidth: '2',
    animationDuration: '2s',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeProgressSpinner
        v-bind="$attrs"
        :strokeWidth="strokeWidth"
        :fill="fill"
        :animationDuration="animationDuration"
    >
        <slot />
    </PrimeProgressSpinner>
</template>

