<script setup lang="ts">
import PrimeAutocomplete from 'primevue/autocomplete';

interface Props {
    modelValue?: any;
    suggestions?: any[];
    optionLabel?: string | ((option: any) => string);
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
    multiple?: boolean;
    dropdown?: boolean;
    showClear?: boolean;
    loading?: boolean;
    forceSelection?: boolean;
    typeahead?: boolean;
    minLength?: number;
    delay?: number;
    scrollHeight?: string;
    optionDisabled?: string | ((option: any) => boolean);
    optionGroupLabel?: string | ((option: any) => string);
    optionGroupChildren?: string | ((option: any) => any[]);
    dataKey?: string;
    completeOnFocus?: boolean;
}

withDefaults(defineProps<Props>(), {
    fluid: true,
    typeahead: true,
    minLength: 1,
    delay: 300,
    scrollHeight: '14rem',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeAutocomplete
        v-bind="$attrs"
        :modelValue="modelValue"
        :suggestions="suggestions"
        :optionLabel="optionLabel"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
        :multiple="multiple"
        :dropdown="dropdown"
        :showClear="showClear"
        :loading="loading"
        :forceSelection="forceSelection"
        :typeahead="typeahead"
        :minLength="minLength"
        :delay="delay"
        :scrollHeight="scrollHeight"
        :optionDisabled="optionDisabled"
        :optionGroupLabel="optionGroupLabel"
        :optionGroupChildren="optionGroupChildren"
        :dataKey="dataKey"
        :completeOnFocus="completeOnFocus"
    >
        <slot />
    </PrimeAutocomplete>
</template>

