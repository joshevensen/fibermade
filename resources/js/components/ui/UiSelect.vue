<script setup lang="ts">
import PrimeSelect from 'primevue/select';

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
    fluid?: boolean;
    scrollHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    scrollHeight: '14rem',
    size: 'small', 
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeSelect
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
        :fluid="fluid"
        :scrollHeight="scrollHeight"
    >
        <slot />
    </PrimeSelect>
</template>

