<script setup lang="ts">
import PrimeInputGroupAddon from 'primevue/inputgroupaddon';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeInputGroupAddon v-bind="$attrs">
        <slot />
    </PrimeInputGroupAddon>
</template>

