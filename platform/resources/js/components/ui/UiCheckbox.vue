<script lang="ts">
import type { CheckboxTokenSections } from '@primeuix/themes/types/checkbox';

export const checkboxRoot: CheckboxTokenSections.Root = {
    borderRadius: '{border.radius.sm}',
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

export const checkboxIcon: CheckboxTokenSections.Icon = {
    size: '0.875rem',
    color: '{form.field.color}',
    checkedColor: '{primary.contrast.color}',
    checkedHoverColor: '{primary.contrast.color}',
    disabledColor: '{form.field.disabled.color}',
    sm: {
        size: '0.75rem'
    },
    lg: {
        size: '1rem'
    }
};
</script>

<script setup lang="ts">
import { useAttrs, computed } from 'vue';

const props = defineProps<{
    modelValue?: unknown;
    binary?: boolean;
    indeterminate?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    required?: boolean;
}>();

const attrs = useAttrs();
const emit = defineEmits<{ 'update:modelValue': [value: unknown] }>();

const effectiveModelValue = computed(() => {
    const formValue = (attrs as { value?: unknown }).value;
    if (formValue !== undefined && formValue !== null) {
        if (typeof formValue === 'boolean') return formValue;
        if (props.binary) return formValue === true || formValue === 'on' || formValue === 1;
        return formValue;
    }
    return props.modelValue;
});

function isChecked(value: unknown): boolean {
    return value === true || value === 1 || value === 'on';
}

const inputAttrs = computed(() => {
    const { onChange: _onChange, value: _value, ...rest } = attrs as Record<string, unknown> & {
        onChange?: (e: Event) => void;
        value?: unknown;
    };
    return rest;
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <!-- PrimeVue Checkbox replaced with native HTML checkbox (form state sync issues with d_value). -->
    <!--
    <PrimeCheckbox
        v-bind="checkboxAttrs"
        :modelValue="effectiveModelValue"
        :binary="binary"
        :indeterminate="indeterminate"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :readonly="readonly"
        :required="required"
        @update:modelValue="(v: unknown) => emit('update:modelValue', v)"
    >
        <slot />
    </PrimeCheckbox>
    -->
    <input
        type="checkbox"
        v-bind="inputAttrs"
        :checked="isChecked(effectiveModelValue)"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-invalid="invalid || undefined"
        class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500"
        @change="
            (e: Event) => {
                const target = (e?.target ?? null) as HTMLInputElement | null;
                emit('update:modelValue', target ? target.checked : false);
            }
        "
    />
</template>
