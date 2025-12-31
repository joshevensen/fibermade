<script lang="ts">
import type { ToggleSwitchTokenSections } from '@primeuix/themes/types/toggleswitch';

export const toggleSwitchRoot: ToggleSwitchTokenSections.Root = {
    width: '2.5rem',
    height: '1.5rem',
    borderRadius: '30px',
    gap: '0.25rem',
    shadow: '{form.field.shadow}',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    },
    borderWidth: '1px',
    borderColor: 'transparent',
    hoverBorderColor: 'transparent',
    checkedBorderColor: 'transparent',
    checkedHoverBorderColor: 'transparent',
    invalidBorderColor: '{form.field.invalid.border.color}',
    transitionDuration: '{form.field.transition.duration}',
    slideDuration: '0.2s'
};

export const toggleSwitchHandle: ToggleSwitchTokenSections.Handle = {
    borderRadius: '50%',
    size: '1rem'
};

export const toggleSwitchLight = {
    root: {
        background: '{surface.300}',
        disabledBackground: '{form.field.disabled.background}',
        hoverBackground: '{surface.400}',
        checkedBackground: '{primary.color}',
        checkedHoverBackground: '{primary.hover.color}'
    },
    handle: {
        background: '{surface.0}',
        disabledBackground: '{form.field.disabled.color}',
        hoverBackground: '{surface.0}',
        checkedBackground: '{surface.0}',
        checkedHoverBackground: '{surface.0}',
        color: '{text.muted.color}',
        hoverColor: '{text.color}',
        checkedColor: '{primary.color}',
        checkedHoverColor: '{primary.hover.color}'
    }
};

export const toggleSwitchDark = {
    root: {
        background: '{surface.700}',
        disabledBackground: '{surface.600}',
        hoverBackground: '{surface.600}',
        checkedBackground: '{primary.color}',
        checkedHoverBackground: '{primary.hover.color}'
    },
    handle: {
        background: '{surface.400}',
        disabledBackground: '{surface.900}',
        hoverBackground: '{surface.300}',
        checkedBackground: '{surface.900}',
        checkedHoverBackground: '{surface.900}',
        color: '{surface.900}',
        hoverColor: '{surface.800}',
        checkedColor: '{primary.color}',
        checkedHoverColor: '{primary.hover.color}'
    }
};
</script>

<script setup lang="ts">
import PrimeToggleSwitch from 'primevue/toggleswitch';

interface Props {
    modelValue?: boolean | string;
    trueValue?: any;
    falseValue?: any;
    invalid?: boolean;
    disabled?: boolean;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    trueValue: true,
    falseValue: false,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeToggleSwitch
        v-bind="$attrs"
        :modelValue="modelValue"
        :trueValue="trueValue"
        :falseValue="falseValue"
        :invalid="invalid"
        :disabled="disabled"
        :readonly="readonly"
    >
        <slot />
    </PrimeToggleSwitch>
</template>

