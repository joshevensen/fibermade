<script lang="ts">
import type { InputTextTokenSections } from '@primeuix/themes/types/inputtext';

export const inputTextRoot: InputTextTokenSections.Root = {
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
import PrimeInputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { computed } from 'vue';

interface Props {
    modelValue?: string | null;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
    icon?: string;
    iconPos?: 'start' | 'end';
}

const props = withDefaults(defineProps<Props>(), {
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});

const inputTextPt = computed(() =>
    props.fluid ? { root: { class: 'min-w-0 w-full' } } : undefined,
);
</script>

<template>
    <IconField v-if="props.icon" class="min-w-0 w-full">
        <InputIcon v-if="props.iconPos !== 'end'" :class="props.icon" />
        <PrimeInputText
            v-bind="$attrs"
            :modelValue="props.modelValue"
            :size="props.size"
            :invalid="props.invalid"
            :variant="props.variant"
            :disabled="props.disabled"
            :placeholder="props.placeholder"
            :required="props.required"
            :fluid="props.fluid"
            :pt="inputTextPt"
        >
            <slot />
        </PrimeInputText>
        <InputIcon v-if="props.iconPos === 'end'" :class="props.icon" />
    </IconField>
    <PrimeInputText
        v-else
        v-bind="$attrs"
        :modelValue="props.modelValue"
        :size="props.size"
        :invalid="props.invalid"
        :variant="props.variant"
        :disabled="props.disabled"
        :placeholder="props.placeholder"
        :required="props.required"
        :fluid="props.fluid"
        :pt="inputTextPt"
    >
        <slot />
    </PrimeInputText>
</template>

