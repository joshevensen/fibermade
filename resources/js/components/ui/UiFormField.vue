<script setup lang="ts">
import { computed } from 'vue';
import { FormField as PrimeFormField } from '@primevue/forms';
import UiMessage from '@/components/ui/UiMessage.vue';

interface Props {
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
}

const props = defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});

// Use name as the field ID for label-input association
const fieldId = computed(() => props.name);
</script>

<template>
    <PrimeFormField
        v-bind="$attrs"
        :name="name"
        :initialValue="initialValue"
        :resolver="resolver"
        :as="as"
        :asChild="asChild"
        :validateOnBlur="validateOnBlur"
        :validateOnSubmit="validateOnSubmit"
        :validateOnValueUpdate="validateOnValueUpdate"
        :validateOnMount="validateOnMount"
    >
        <template v-slot="$field">
            <label v-if="label" :for="fieldId">{{ label }}</label>
            <slot v-bind="{ ...$field, id: fieldId }" />
            <UiMessage
                v-if="$field?.invalid || serverError"
                severity="error"
                size="small"
                variant="simple"
            >
                {{ serverError || $field.error?.message }}
            </UiMessage>
        </template>
    </PrimeFormField>
</template>

