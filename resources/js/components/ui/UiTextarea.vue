<script setup lang="ts">
import PrimeTextarea from 'primevue/textarea';

interface Props {
    modelValue?: string | null;
    autoResize?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
}

withDefaults(defineProps<Props>(), {
    size: 'small', 
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeTextarea
        v-bind="$attrs"
        :modelValue="modelValue"
        :autoResize="autoResize"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
    >
        <slot />
    </PrimeTextarea>
</template>

