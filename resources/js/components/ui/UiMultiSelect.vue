<script lang="ts">
import type { MultiSelectTokenSections } from '@primeuix/themes/types/multiselect';

export const multiSelectRoot: MultiSelectTokenSections.Root = {
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

export const multiSelectDropdown: MultiSelectTokenSections.Dropdown = {
    width: '2.5rem',
    color: '{form.field.icon.color}'
};

export const multiSelectOverlay: MultiSelectTokenSections.Overlay = {
    background: '{overlay.select.background}',
    borderColor: '{overlay.select.border.color}',
    borderRadius: '{overlay.select.border.radius}',
    color: '{overlay.select.color}',
    shadow: '{overlay.select.shadow}'
};

export const multiSelectList: MultiSelectTokenSections.List = {
    padding: '{list.padding}',
    gap: '{list.gap}',
    header: {
        padding: '{list.header.padding}'
    }
};

export const multiSelectOption: MultiSelectTokenSections.Option = {
    focusBackground: '{list.option.focus.background}',
    selectedBackground: '{list.option.selected.background}',
    selectedFocusBackground: '{list.option.selected.focus.background}',
    color: '{list.option.color}',
    focusColor: '{list.option.focus.color}',
    selectedColor: '{list.option.selected.color}',
    selectedFocusColor: '{list.option.selected.focus.color}',
    padding: '{list.option.padding}',
    borderRadius: '{list.option.border.radius}',
    gap: '0.5rem'
};

export const multiSelectOptionGroup: MultiSelectTokenSections.OptionGroup = {
    background: '{list.option.group.background}',
    color: '{list.option.group.color}',
    fontWeight: '{list.option.group.font.weight}',
    padding: '{list.option.group.padding}'
};

export const multiSelectClearIcon: MultiSelectTokenSections.ClearIcon = {
    color: '{form.field.icon.color}'
};

export const multiSelectChip: MultiSelectTokenSections.Chip = {
    borderRadius: '{border.radius.sm}'
};

export const multiSelectEmptyMessage: MultiSelectTokenSections.EmptyMessage = {
    padding: '{list.option.padding}'
};
</script>

<script setup lang="ts">
import PrimeMultiSelect from 'primevue/multiselect';

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
    display?: 'comma' | 'chip';
    maxSelectedLabels?: number;
    selectionLimit?: number;
    fluid?: boolean;
    scrollHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    optionLabel: 'label',
    optionValue: 'value',
    scrollHeight: '14rem',
    display: 'comma',
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMultiSelect
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
        :display="display"
        :maxSelectedLabels="maxSelectedLabels"
        :selectionLimit="selectionLimit"
        :fluid="fluid"
        :scrollHeight="scrollHeight"
    >
        <slot />
    </PrimeMultiSelect>
</template>

