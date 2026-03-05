<script setup lang="ts">
import { computed } from 'vue';
import { FormField as PrimeFormField } from '@primevue/forms';
import UiMessage from '@/components/ui/UiMessage.vue';

type LabelPosition = 'top' | 'left' | 'right';

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
    labelPosition?: LabelPosition;
    size?: 'small' | 'large';
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    labelPosition: 'top',
});

defineOptions({
    inheritAttrs: false,
});

// Use name as the field ID for label-input association
const fieldId = computed(() => props.name);

const isHorizontal = computed(() => props.labelPosition === 'left' || props.labelPosition === 'right');
const isLabelRight = computed(() => props.labelPosition === 'right');
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
            <div
                :class="{
                    'flex items-center': isHorizontal,
                    'gap-2': isHorizontal,
                    'min-w-0 w-full': true,
                }"
            >
                <!-- Label positioned based on labelPosition prop -->
                <label
                    v-if="label || $slots.label"
                    :for="fieldId"
                    :class="{
                        'flex justify-between w-full mb-1': labelPosition === 'top',
                        'flex items-center gap-2 shrink-0': isHorizontal && !isLabelRight,
                        'flex items-center gap-2 shrink-0 order-2': isHorizontal && isLabelRight,
                        'text-sm': size === 'small',
                    }"
                >
                    <span class="min-w-0">
                        <slot name="label">{{ label }}</slot>
                        <small v-if="required" class="text-surface-500">(required)</small>
                    </span>
                    <span v-if="labelPosition === 'top'" class="shrink-0">
                        <slot name="extra" />
                    </span>
                </label>

                <!-- Input wrapper with proper order for right-side labels -->
                <div
                    :class="{
                        'flex-1 min-w-0 w-full': isHorizontal && !isLabelRight,
                        'order-1': isHorizontal && isLabelRight,
                        'min-w-0 w-full': labelPosition === 'top',
                    }"
                >
                    <slot v-bind="{ ...$field, id: fieldId }" />
                </div>
            </div>
            
            <!-- Error message always full width underneath -->
            <UiMessage
                v-if="$field?.invalid || serverError"
                severity="error"
                size="small"
                variant="simple"
                :class="{
                    'mt-1': labelPosition === 'top' || isHorizontal,
                }"
            >
                {{ serverError || $field.error?.message }}
            </UiMessage>
        </template>
    </PrimeFormField>
</template>

