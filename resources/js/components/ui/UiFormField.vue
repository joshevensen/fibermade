<script setup lang="ts">
import { FormField as PrimeFormField } from '@primevue/forms';
import UiMessage from '@/components/ui/UiMessage.vue';

interface Props {
    name: string;
    label?: string;
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

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
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
            <label v-if="label" :for="name">{{ label }}</label>
            <slot :$field="$field" />
            <UiMessage
                v-if="$field?.invalid"
                severity="error"
                size="small"
                variant="simple"
            >
                {{ $field.error?.message }}
            </UiMessage>
        </template>
    </PrimeFormField>
</template>

