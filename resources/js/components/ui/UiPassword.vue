<script setup lang="ts">
import PrimePassword from 'primevue/password';

interface Props {
    modelValue?: string | null;
    feedback?: boolean;
    toggleMask?: boolean;
    promptLabel?: string;
    weakLabel?: string;
    mediumLabel?: string;
    strongLabel?: string;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    feedback: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimePassword
        v-bind="$attrs"
        :modelValue="modelValue"
        :feedback="feedback"
        :toggleMask="toggleMask"
        :promptLabel="promptLabel"
        :weakLabel="weakLabel"
        :mediumLabel="mediumLabel"
        :strongLabel="strongLabel"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
    >
        <slot />
    </PrimePassword>
</template>

