<script setup lang="ts">
import PrimeCheckbox from 'primevue/checkbox';

interface Props {
    modelValue?: any;
    binary?: boolean;
    indeterminate?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    required?: boolean;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeCheckbox
        v-bind="$attrs"
        :modelValue="modelValue"
        :binary="binary"
        :indeterminate="indeterminate"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :readonly="readonly"
        :required="required"
    >
        <slot />
    </PrimeCheckbox>
</template>

