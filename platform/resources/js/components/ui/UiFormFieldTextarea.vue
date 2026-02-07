<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiTextarea from '@/components/ui/UiTextarea.vue';

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
    // UiTextarea props
    autoResize?: boolean;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
}

withDefaults(defineProps<Props>(), {
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
            <UiTextarea
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
                :autoResize="autoResize"
                :size="size"
                :invalid="invalid"
                :variant="variant"
                :disabled="disabled"
                :placeholder="placeholder"
                :required="required"
                :fluid="fluid"
            />
        </template>
    </UiFormField>
</template>

