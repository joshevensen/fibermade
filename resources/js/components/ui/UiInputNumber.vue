<script lang="ts">
import type { InputNumberTokenSections } from '@primeuix/themes/types/inputnumber';

export const inputNumberRoot: InputNumberTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const inputNumberButton: InputNumberTokenSections.Button = {
    width: '2.5rem',
    borderRadius: '{form.field.border.radius}',
    verticalPadding: '{form.field.padding.y}'
};

export const inputNumberLight = {
    button: {
        background: 'transparent',
        hoverBackground: '{surface.100}',
        activeBackground: '{surface.200}',
        borderColor: '{form.field.border.color}',
        hoverBorderColor: '{form.field.border.color}',
        activeBorderColor: '{form.field.border.color}',
        color: '{surface.400}',
        hoverColor: '{surface.500}',
        activeColor: '{surface.600}'
    }
};

export const inputNumberDark = {
    button: {
        background: 'transparent',
        hoverBackground: '{surface.800}',
        activeBackground: '{surface.700}',
        borderColor: '{form.field.border.color}',
        hoverBorderColor: '{form.field.border.color}',
        activeBorderColor: '{form.field.border.color}',
        color: '{surface.400}',
        hoverColor: '{surface.300}',
        activeColor: '{surface.200}'
    }
};
</script>

<script setup lang="ts">
import PrimeInputNumber from 'primevue/inputnumber';
import type { PassThrough } from 'primevue';

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
    pt?: PassThrough;
}

const props = withDefaults(defineProps<Props>(), {
    step: 1,
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
        :pt="pt"
    >
        <slot />
    </PrimeInputNumber>
</template>

