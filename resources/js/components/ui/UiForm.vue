<script setup lang="ts">
import { Form as PrimeForm } from '@primevue/forms';

interface Props {
    initialValues?: Record<string, any>;
    resolver?: (params: { values: Record<string, any> }) => {
        values?: Record<string, any>;
        errors?: Record<string, Array<{ message: string; type?: string }>>;
    };
    validateOnBlur?: boolean | string[];
    validateOnSubmit?: boolean | string[];
    validateOnValueUpdate?: boolean | string[];
    validateOnMount?: boolean | string[];
}

const props = withDefaults(defineProps<Props>(), {
    validateOnBlur: true,
    validateOnSubmit: true,
    validateOnValueUpdate: false,
    validateOnMount: false,
});

defineOptions({
    inheritAttrs: false,
});

const emit = defineEmits<{
    submit: [event: {
        valid: boolean;
        values: Record<string, any>;
        errors: Record<string, any>;
        states: Record<string, any>;
        reset: () => void;
    }];
}>();
</script>

<template>
    <PrimeForm
        v-bind="$attrs"
        :initialValues="initialValues"
        :resolver="resolver"
        :validateOnBlur="validateOnBlur"
        :validateOnSubmit="validateOnSubmit"
        :validateOnValueUpdate="validateOnValueUpdate"
        :validateOnMount="validateOnMount"
        class="space-y-6"
        @submit="emit('submit', $event)"
    >
        <slot />
    </PrimeForm>
</template>

