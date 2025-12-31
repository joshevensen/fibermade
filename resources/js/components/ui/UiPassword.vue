<script lang="ts">
import type { PasswordTokenSections } from '@primeuix/themes/types/password';

export const passwordMeter: PasswordTokenSections.Meter = {
    background: '{content.border.color}',
    borderRadius: '{content.border.radius}',
    height: '.75rem'
};

export const passwordIcon: PasswordTokenSections.Icon = {
    color: '{form.field.icon.color}'
};

export const passwordOverlay: PasswordTokenSections.Overlay = {
    background: '{overlay.popover.background}',
    borderColor: '{overlay.popover.border.color}',
    borderRadius: '{overlay.popover.border.radius}',
    color: '{overlay.popover.color}',
    padding: '{overlay.popover.padding}',
    shadow: '{overlay.popover.shadow}'
};

export const passwordContent: PasswordTokenSections.Content = {
    gap: '0.5rem'
};

export const passwordLight = {
    strength: {
        weakBackground: '{red.500}',
        mediumBackground: '{amber.500}',
        strongBackground: '{green.500}'
    }
};

export const passwordDark = {
    strength: {
        weakBackground: '{red.400}',
        mediumBackground: '{amber.400}',
        strongBackground: '{green.400}'
    }
};
</script>

<script setup lang="ts">
import PrimePassword from 'primevue/password';

interface Props {
    modelValue?: string | null;
    feedback?: boolean;
    toggleMask?: boolean;
    promptLabel?: string;
    weakLabel?: string;
    mediumLabel?: string;
    strongLabel?: string;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimePassword
        v-bind="$attrs"
        :modelValue="modelValue"
        :feedback="feedback"
        :toggleMask="toggleMask"
        :promptLabel="promptLabel"
        :weakLabel="weakLabel"
        :mediumLabel="mediumLabel"
        :strongLabel="strongLabel"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
    >
        <slot />
    </PrimePassword>
</template>

