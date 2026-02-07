<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiToggleSwitch from '@/components/ui/UiToggleSwitch.vue';

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
    labelPosition?: 'top' | 'left' | 'right';
    required?: boolean;
    // UiToggleSwitch props
    trueValue?: any;
    falseValue?: any;
    invalid?: boolean;
    disabled?: boolean;
    readonly?: boolean;
}

defineProps<Props>();

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
        :labelPosition="labelPosition ?? 'right'"
        :required="required"
    >
        <template #default="{ props: fieldProps, id }">
            <UiToggleSwitch
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
                :trueValue="trueValue"
                :falseValue="falseValue"
                :invalid="invalid"
                :disabled="disabled"
                :readonly="readonly"
            />
        </template>
    </UiFormField>
</template>

