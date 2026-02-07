<script setup lang="ts">
import PrimeTabPanel from 'primevue/tabpanel';
import type { Component } from 'vue';

interface Props {
    value: string | number;
    as?: string | Component;
    asChild?: boolean;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeTabPanel
        v-bind="$attrs"
        :value="value"
        :as="as"
        :asChild="asChild"
    >
        <slot />
    </PrimeTabPanel>
</template>

