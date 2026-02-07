<script lang="ts">
import type { TextareaTokenSections } from '@primeuix/themes/types/textarea';

export const textareaRoot: TextareaTokenSections.Root = {
    background: '{form.field.background}',
    disabledBackground: '{form.field.disabled.background}',
    filledBackground: '{form.field.filled.background}',
    filledHoverBackground: '{form.field.filled.hover.background}',
    filledFocusBackground: '{form.field.filled.focus.background}',
    borderColor: '{form.field.border.color}',
    hoverBorderColor: '{form.field.hover.border.color}',
    focusBorderColor: '{form.field.focus.border.color}',
    invalidBorderColor: '{form.field.invalid.border.color}',
    color: '{form.field.color}',
    disabledColor: '{form.field.disabled.color}',
    placeholderColor: '{form.field.placeholder.color}',
    invalidPlaceholderColor: '{form.field.invalid.placeholder.color}',
    shadow: '{form.field.shadow}',
    paddingX: '{form.field.padding.x}',
    paddingY: '{form.field.padding.y}',
    borderRadius: '{form.field.border.radius}',
    focusRing: {
        width: '{form.field.focus.ring.width}',
        style: '{form.field.focus.ring.style}',
        color: '{form.field.focus.ring.color}',
        offset: '{form.field.focus.ring.offset}',
        shadow: '{form.field.focus.ring.shadow}'
    },
    transitionDuration: '{form.field.transition.duration}',
    sm: {
        fontSize: '{form.field.sm.font.size}',
        paddingX: '{form.field.sm.padding.x}',
        paddingY: '{form.field.sm.padding.y}'
    },
    lg: {
        fontSize: '{form.field.lg.font.size}',
        paddingX: '{form.field.lg.padding.x}',
        paddingY: '{form.field.lg.padding.y}'
    }
};
</script>

<script setup lang="ts">
import PrimeTextarea from 'primevue/textarea';

interface Props {
    modelValue?: string | null;
    autoResize?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
}

withDefaults(defineProps<Props>(), {
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeTextarea
        v-bind="$attrs"
        :modelValue="modelValue"
        :autoResize="autoResize"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
    >
        <slot />
    </PrimeTextarea>
</template>

