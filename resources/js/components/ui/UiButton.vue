<script setup lang="ts">
import PrimeButton from 'primevue/button';

interface Props {
    severity?: 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'danger' | 'help' | 'contrast';
    outlined?: boolean;
    text?: boolean;
    size?: 'small' | 'large';
    icon?: string;
    iconPos?: 'left' | 'right' | 'top' | 'bottom';
    loading?: boolean;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    label?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
    size: 'small',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeButton
        v-bind="$attrs"
        :severity="severity"
        :outlined="outlined"
        :text="text"
        :size="size"
        :icon="icon"
        :iconPos="iconPos"
        :loading="loading"
        :disabled="disabled || loading"
        :type="type"
        :label="label"
    >
        <slot />
    </PrimeButton>
</template>

