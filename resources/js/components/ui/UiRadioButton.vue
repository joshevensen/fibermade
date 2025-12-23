<script setup lang="ts">
import PrimeRadioButton from 'primevue/radiobutton';

interface Props {
    modelValue?: any;
    value?: any;
    binary?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeRadioButton
        v-bind="$attrs"
        :modelValue="modelValue"
        :value="value"
        :binary="binary"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :readonly="readonly"
    >
        <slot />
    </PrimeRadioButton>
</template>

