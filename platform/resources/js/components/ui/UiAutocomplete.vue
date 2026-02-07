<script lang="ts">
import type { AutoCompleteTokenSections } from '@primeuix/themes/types/autocomplete';

export const autocompleteRoot: AutoCompleteTokenSections.Root = {
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
    transitionDuration: '{form.field.transition.duration}'
};

export const autocompleteOverlay: AutoCompleteTokenSections.Overlay = {
    background: '{overlay.select.background}',
    borderColor: '{overlay.select.border.color}',
    borderRadius: '{overlay.select.border.radius}',
    color: '{overlay.select.color}',
    shadow: '{overlay.select.shadow}'
};

export const autocompleteList: AutoCompleteTokenSections.List = {
    padding: '{list.padding}',
    gap: '{list.gap}'
};

export const autocompleteOption: AutoCompleteTokenSections.Option = {
    focusBackground: '{list.option.focus.background}',
    selectedBackground: '{list.option.selected.background}',
    selectedFocusBackground: '{list.option.selected.focus.background}',
    color: '{list.option.color}',
    focusColor: '{list.option.focus.color}',
    selectedColor: '{list.option.selected.color}',
    selectedFocusColor: '{list.option.selected.focus.color}',
    padding: '{list.option.padding}',
    borderRadius: '{list.option.border.radius}'
};

export const autocompleteOptionGroup: AutoCompleteTokenSections.OptionGroup = {
    background: '{list.option.group.background}',
    color: '{list.option.group.color}',
    fontWeight: '{list.option.group.font.weight}',
    padding: '{list.option.group.padding}'
};

export const autocompleteDropdown: AutoCompleteTokenSections.Dropdown = {
    width: '2.5rem',
    sm: {
        width: '2rem'
    },
    lg: {
        width: '3rem'
    },
    borderColor: '{form.field.border.color}',
    hoverBorderColor: '{form.field.border.color}',
    activeBorderColor: '{form.field.border.color}',
    borderRadius: '{form.field.border.radius}',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};

export const autocompleteChip: AutoCompleteTokenSections.Chip = {
    borderRadius: '{border.radius.sm}'
};

export const autocompleteEmptyMessage: AutoCompleteTokenSections.EmptyMessage = {
    padding: '{list.option.padding}'
};

export const autocompleteLight = {
    chip: {
        focusBackground: '{surface.200}',
        focusColor: '{surface.800}'
    },
    dropdown: {
        background: '{surface.100}',
        hoverBackground: '{surface.200}',
        activeBackground: '{surface.300}',
        color: '{surface.600}',
        hoverColor: '{surface.700}',
        activeColor: '{surface.800}'
    }
};

export const autocompleteDark = {
    chip: {
        focusBackground: '{surface.700}',
        focusColor: '{surface.0}'
    },
    dropdown: {
        background: '{surface.800}',
        hoverBackground: '{surface.700}',
        activeBackground: '{surface.600}',
        color: '{surface.300}',
        hoverColor: '{surface.200}',
        activeColor: '{surface.100}'
    }
};
</script>

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

