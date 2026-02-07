<script lang="ts">
import type { RadioButtonTokenSections } from '@primeuix/themes/types/radiobutton';

export const radioButtonRoot: RadioButtonTokenSections.Root = {
    width: '1.25rem',
    height: '1.25rem',
    background: '{form.field.background}',
    checkedBackground: '{primary.color}',
    checkedHoverBackground: '{primary.hover.color}',
    disabledBackground: '{form.field.disabled.background}',
    filledBackground: '{form.field.filled.background}',
    borderColor: '{form.field.border.color}',
    hoverBorderColor: '{form.field.hover.border.color}',
    focusBorderColor: '{form.field.border.color}',
    checkedBorderColor: '{primary.color}',
    checkedHoverBorderColor: '{primary.hover.color}',
    checkedFocusBorderColor: '{primary.color}',
    checkedDisabledBorderColor: '{form.field.border.color}',
    invalidBorderColor: '{form.field.invalid.border.color}',
    shadow: '{form.field.shadow}',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    },
    transitionDuration: '{form.field.transition.duration}',
    sm: {
        width: '1rem',
        height: '1rem'
    },
    lg: {
        width: '1.5rem',
        height: '1.5rem'
    }
};

export const radioButtonIcon: RadioButtonTokenSections.Icon = {
    size: '0.75rem',
    checkedColor: '{primary.contrast.color}',
    checkedHoverColor: '{primary.contrast.color}',
    disabledColor: '{form.field.disabled.color}',
    sm: {
        size: '0.5rem'
    },
    lg: {
        size: '1rem'
    }
};
</script>

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

