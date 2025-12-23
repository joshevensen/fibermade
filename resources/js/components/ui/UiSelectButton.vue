<script setup lang="ts">
import PrimeSelectButton from 'primevue/selectbutton';

interface Props {
    modelValue?: any;
    options?: any[];
    optionLabel?: string | ((data: any) => string);
    optionValue?: string | ((data: any) => any);
    optionDisabled?: string | ((data: any) => boolean);
    multiple?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    fluid?: boolean;
    allowEmpty?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    multiple: false,
    allowEmpty: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeSelectButton
        v-bind="$attrs"
        :modelValue="modelValue"
        :options="options"
        :optionLabel="optionLabel"
        :optionValue="optionValue"
        :optionDisabled="optionDisabled"
        :multiple="multiple"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :fluid="fluid"
        :allowEmpty="allowEmpty"
    >
        <slot />
    </PrimeSelectButton>
</template>

