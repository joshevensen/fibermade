<script setup lang="ts">
import PrimeMultiSelect from 'primevue/multiselect';

interface Props {
    modelValue?: any;
    options?: any[];
    optionLabel?: string | ((data: any) => string);
    optionValue?: string | ((data: any) => any);
    optionDisabled?: string | ((data: any) => boolean);
    placeholder?: string;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    filter?: boolean;
    filterPlaceholder?: string;
    showClear?: boolean;
    display?: 'comma' | 'chip';
    maxSelectedLabels?: number;
    selectionLimit?: number;
    fluid?: boolean;
    scrollHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    scrollHeight: '14rem',
    display: 'comma',
    size: 'small', 
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMultiSelect
        v-bind="$attrs"
        :modelValue="modelValue"
        :options="options"
        :optionLabel="optionLabel"
        :optionValue="optionValue"
        :optionDisabled="optionDisabled"
        :placeholder="placeholder"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :filter="filter"
        :filterPlaceholder="filterPlaceholder"
        :showClear="showClear"
        :display="display"
        :maxSelectedLabels="maxSelectedLabels"
        :selectionLimit="selectionLimit"
        :fluid="fluid"
        :scrollHeight="scrollHeight"
    >
        <slot />
    </PrimeMultiSelect>
</template>

