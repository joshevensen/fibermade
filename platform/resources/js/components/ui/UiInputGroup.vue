<script setup lang="ts">
import PrimeInputGroup from 'primevue/inputgroup';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeInputGroup v-bind="$attrs">
        <slot />
    </PrimeInputGroup>
</template>

