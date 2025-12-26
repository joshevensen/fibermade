<script setup lang="ts">
import PrimeCard from 'primevue/card';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const pt = computed(() => ({
    root: {
        class: 'bg-surface-0! border border-surface-200 shadow-none!',
    },
}));
</script>

<template>
    <PrimeCard v-bind="$attrs" :pt="pt">
        <template #header>
            <slot name="header" />
        </template>
        <template #title>
            <slot name="title" />
        </template>
        <template #subtitle>
            <slot name="subtitle" />
        </template>
        <template #content>
            <slot name="content" />
        </template>
        <template #footer>
            <slot name="footer" />
        </template>
        <slot />
    </PrimeCard>
</template>

