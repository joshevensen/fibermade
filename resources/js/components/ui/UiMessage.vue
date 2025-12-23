<script setup lang="ts">
import PrimeMessage from 'primevue/message';

interface Props {
    severity?: 'error' | 'secondary' | 'info' | 'success' | 'warn' | 'contrast';
    closable?: boolean;
    sticky?: boolean;
    life?: number;
    icon?: string;
    closeIcon?: string;
    size?: 'small' | 'large';
    variant?: 'outlined' | 'simple';
}

const props = withDefaults(defineProps<Props>(), {
    severity: 'info',
    sticky: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMessage
        v-bind="$attrs"
        :severity="severity"
        :closable="closable"
        :sticky="sticky"
        :life="life"
        :icon="icon"
        :closeIcon="closeIcon"
        :size="size"
        :variant="variant"
    >
        <slot />
    </PrimeMessage>
</template>

