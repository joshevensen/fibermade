<script setup lang="ts">
import PrimeCard from 'primevue/card';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeCard v-bind="$attrs">
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

