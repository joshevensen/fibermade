<script setup lang="ts">
import PrimeTag from 'primevue/tag';

interface Props {
    value?: any;
    severity?: 'secondary' | 'info' | 'success' | 'warn' | 'danger' | 'contrast';
    rounded?: boolean;
    icon?: string;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeTag
        v-bind="$attrs"
        :value="value"
        :severity="severity"
        :rounded="rounded"
        :icon="icon"
    >
        <slot />
    </PrimeTag>
</template>

