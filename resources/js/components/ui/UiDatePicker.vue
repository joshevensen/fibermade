<script lang="ts">
import type { DatePickerTokenSections } from '@primeuix/themes/types/datepicker';

export const datePickerRoot: DatePickerTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const datePickerPanel: DatePickerTokenSections.Panel = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}',
    shadow: '{overlay.popover.shadow}',
    padding: '{overlay.popover.padding}'
};

export const datePickerHeader: DatePickerTokenSections.Header = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    padding: '0 0 0.5rem 0'
};

export const datePickerTitle: DatePickerTokenSections.Title = {
    gap: '0.5rem',
    fontWeight: '500'
};

export const datePickerDropdown: DatePickerTokenSections.Dropdown = {
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

export const datePickerInputIcon: DatePickerTokenSections.InputIcon = {
    color: '{form.field.icon.color}'
};

export const datePickerSelectMonth: DatePickerTokenSections.SelectMonth = {
    hoverBackground: '{content.hover.background}',
    color: '{content.color}',
    hoverColor: '{content.hover.color}',
    padding: '0.25rem 0.5rem',
    borderRadius: '{content.border.radius}'
};

export const datePickerSelectYear: DatePickerTokenSections.SelectYear = {
    hoverBackground: '{content.hover.background}',
    color: '{content.color}',
    hoverColor: '{content.hover.color}',
    padding: '0.25rem 0.5rem',
    borderRadius: '{content.border.radius}'
};

export const datePickerGroup: DatePickerTokenSections.Group = {
    borderColor: '{content.border.color}',
    gap: '{overlay.popover.padding}'
};

export const datePickerDayView: DatePickerTokenSections.DayView = {
    margin: '0.5rem 0 0 0'
};

export const datePickerWeekDay: DatePickerTokenSections.WeekDay = {
    padding: '0.25rem',
    fontWeight: '500',
    color: '{content.color}'
};

export const datePickerDate: DatePickerTokenSections.Date = {
    hoverBackground: '{content.hover.background}',
    selectedBackground: '{primary.color}',
    rangeSelectedBackground: '{highlight.background}',
    color: '{content.color}',
    hoverColor: '{content.hover.color}',
    selectedColor: '{primary.contrast.color}',
    rangeSelectedColor: '{highlight.color}',
    width: '2rem',
    height: '2rem',
    borderRadius: '50%',
    padding: '0.25rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};

export const datePickerMonthView: DatePickerTokenSections.MonthView = {
    margin: '0.5rem 0 0 0'
};

export const datePickerMonth: DatePickerTokenSections.Month = {
    padding: '0.375rem',
    borderRadius: '{content.border.radius}'
};

export const datePickerYearView: DatePickerTokenSections.YearView = {
    margin: '0.5rem 0 0 0'
};

export const datePickerYear: DatePickerTokenSections.Year = {
    padding: '0.375rem',
    borderRadius: '{content.border.radius}'
};

export const datePickerButtonbar: DatePickerTokenSections.Buttonbar = {
    padding: '0.5rem 0 0 0',
    borderColor: '{content.border.color}'
};

export const datePickerTimePicker: DatePickerTokenSections.TimePicker = {
    padding: '0.5rem 0 0 0',
    borderColor: '{content.border.color}',
    gap: '0.5rem',
    buttonGap: '0.25rem'
};

export const datePickerLight = {
    dropdown: {
        background: '{surface.100}',
        hoverBackground: '{surface.200}',
        activeBackground: '{surface.300}',
        color: '{surface.600}',
        hoverColor: '{surface.700}',
        activeColor: '{surface.800}'
    },
    today: {
        background: '{surface.200}',
        color: '{surface.900}'
    }
};

export const datePickerDark = {
    dropdown: {
        background: '{surface.800}',
        hoverBackground: '{surface.700}',
        activeBackground: '{surface.600}',
        color: '{surface.300}',
        hoverColor: '{surface.200}',
        activeColor: '{surface.100}'
    },
    today: {
        background: '{surface.700}',
        color: '{surface.0}'
    }
};
</script>

<script setup lang="ts">
import PrimeDatePicker from 'primevue/datepicker';

interface Props {
    modelValue?: Date | Date[] | null;
    selectionMode?: 'single' | 'multiple' | 'range';
    dateFormat?: string;
    showIcon?: boolean;
    icon?: string;
    inline?: boolean;
    minDate?: Date;
    maxDate?: Date;
    disabledDates?: Date[];
    disabledDays?: number[];
    showTime?: boolean;
    timeOnly?: boolean;
    hourFormat?: '12' | '24';
    showClear?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    placeholder?: string;
    fluid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selectionMode: 'single',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDatePicker
        v-bind="$attrs"
        :modelValue="modelValue"
        :selectionMode="selectionMode"
        :dateFormat="dateFormat"
        :showIcon="showIcon"
        :icon="icon"
        :inline="inline"
        :minDate="minDate"
        :maxDate="maxDate"
        :disabledDates="disabledDates"
        :disabledDays="disabledDays"
        :showTime="showTime"
        :timeOnly="timeOnly"
        :hourFormat="hourFormat"
        :showClear="showClear"
        :size="size"
        :invalid="invalid"
        :disabled="disabled"
        :variant="variant"
        :readonly="readonly"
        :placeholder="placeholder"
        :fluid="fluid"
    >
        <slot />
    </PrimeDatePicker>
</template>

