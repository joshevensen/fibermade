<script setup lang="ts">
import PrimePanel from 'primevue/panel';

interface Props {
    header?: string;
    toggleable?: boolean;
    collapsed?: boolean;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimePanel
        v-bind="$attrs"
        :header="header"
        :toggleable="toggleable"
        :collapsed="collapsed"
    >
        <slot />
    </PrimePanel>
</template>

