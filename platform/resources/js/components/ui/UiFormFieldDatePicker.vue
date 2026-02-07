<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiDatePicker from '@/components/ui/UiDatePicker.vue';

interface Props {
    // UiFormField props
    name: string;
    label?: string;
    serverError?: string;
    initialValue?: any;
    resolver?: (params: { value: any }) => {
        errors?: Array<{ message: string; type?: string }>;
    };
    as?: string;
    asChild?: boolean;
    validateOnBlur?: boolean;
    validateOnSubmit?: boolean;
    validateOnValueUpdate?: boolean;
    validateOnMount?: boolean;
    required?: boolean;
    // UiDatePicker props
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
    fluid: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <UiFormField
        :name="name"
        :label="label"
        :serverError="serverError"
        :initialValue="initialValue"
        :resolver="resolver"
        :as="as"
        :asChild="asChild"
        :validateOnBlur="validateOnBlur"
        :validateOnSubmit="validateOnSubmit"
        :validateOnValueUpdate="validateOnValueUpdate"
        :validateOnMount="validateOnMount"
        :required="required"
        :size="size"
    >
        <template #default="{ props: fieldProps, id }">
            <UiDatePicker
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
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
            />
        </template>
    </UiFormField>
</template>

