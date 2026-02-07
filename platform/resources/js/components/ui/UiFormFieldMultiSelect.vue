<script setup lang="ts">
import UiFormField from '@/components/ui/UiFormField.vue';
import UiMultiSelect from '@/components/ui/UiMultiSelect.vue';

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
    // UiMultiSelect props
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
        :labelPosition="labelPosition"
        :required="required"
        :size="size"
    >
        <template #default="{ props: fieldProps, id }">
            <UiMultiSelect
                v-bind="{ ...fieldProps, ...$attrs }"
                :id="id"
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
            />
        </template>
    </UiFormField>
</template>

