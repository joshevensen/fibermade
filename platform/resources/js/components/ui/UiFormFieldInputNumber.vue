<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';

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
    // UiInputNumber props
    showButtons?: boolean;
    buttonLayout?: 'horizontal' | 'vertical' | 'stacked';
    min?: number;
    max?: number;
    step?: number;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    placeholder?: string;
    showClear?: boolean;
    fluid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    step: 1,
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
            <UiInputNumber
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
                :showButtons="showButtons"
                :buttonLayout="buttonLayout"
                :min="min"
                :max="max"
                :step="step"
                :size="size"
                :invalid="invalid"
                :disabled="disabled"
                :variant="variant"
                :readonly="readonly"
                :placeholder="placeholder"
                :showClear="showClear"
                :fluid="fluid"
            />
        </template>
    </UiFormField>
</template>

