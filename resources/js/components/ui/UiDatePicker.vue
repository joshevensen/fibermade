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

