<script lang="ts">
import type { SelectButtonTokenSections } from '@primeuix/themes/types/selectbutton';

export const selectButtonRoot: SelectButtonTokenSections.Root = {
    borderRadius: '{form.field.border.radius}'
};

export const selectButtonLight = {
    root: {
        invalidBorderColor: '{form.field.invalid.border.color}'
    }
};

export const selectButtonDark = {
    root: {
        invalidBorderColor: '{form.field.invalid.border.color}'
    }
};
</script>

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
    optionLabel: 'label',
    optionValue: 'value',
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

