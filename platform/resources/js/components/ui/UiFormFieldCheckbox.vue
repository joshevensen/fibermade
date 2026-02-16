<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';

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
    // UiCheckbox props
    binary?: boolean;
    indeterminate?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    required?: boolean;
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
        <template v-if="$slots.label" #label>
            <slot name="label" />
        </template>
        <template #default="{ props: fieldProps, id }">
            <UiCheckbox
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
                :binary="binary"
                :indeterminate="indeterminate"
                :size="size"
                :invalid="invalid"
                :disabled="disabled"
                :variant="variant"
                :readonly="readonly"
                :required="required"
            />
        </template>
    </UiFormField>
</template>

