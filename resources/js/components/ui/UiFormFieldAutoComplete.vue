<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiAutocomplete from '@/components/ui/UiAutocomplete.vue';

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
    // UiAutocomplete props
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

const props = withDefaults(defineProps<Props>(), {
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
            <UiAutocomplete
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
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
            />
        </template>
    </UiFormField>
</template>

