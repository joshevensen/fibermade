<script setup lang="ts">
import PrimeToggleSwitch from 'primevue/toggleswitch';

interface Props {
    modelValue?: boolean | string;
    trueValue?: any;
    falseValue?: any;
    invalid?: boolean;
    disabled?: boolean;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    trueValue: true,
    falseValue: false,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeToggleSwitch
        v-bind="$attrs"
        :modelValue="modelValue"
        :trueValue="trueValue"
        :falseValue="falseValue"
        :invalid="invalid"
        :disabled="disabled"
        :readonly="readonly"
    >
        <slot />
    </PrimeToggleSwitch>
</template>

