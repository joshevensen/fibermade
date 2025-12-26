<script setup lang="ts">
import PrimeInputNumber from 'primevue/inputnumber';

interface Props {
    modelValue?: number | null;
    showButtons?: boolean;
    buttonLayout?: 'horizontal' | 'vertical' | 'stacked';
    min?: number;
    max?: number;
    step?: number;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    placeholder?: string;
    showClear?: boolean;
    fluid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    step: 1,
    size: 'small', 
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeInputNumber
        v-bind="$attrs"
        :modelValue="modelValue"
        :showButtons="showButtons"
        :buttonLayout="buttonLayout"
        :min="min"
        :max="max"
        :step="step"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :readonly="readonly"
        :placeholder="placeholder"
        :showClear="showClear"
        :fluid="fluid"
    >
        <slot />
    </PrimeInputNumber>
</template>

