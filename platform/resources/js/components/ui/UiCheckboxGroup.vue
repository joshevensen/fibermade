<script setup lang="ts">
import PrimeCheckboxGroup from 'primevue/checkboxgroup';

interface Props {
    modelValue?: any;
    name?: string;
    invalid?: boolean;
}

const props = defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: any];
}>();
</script>

<template>
    <PrimeCheckboxGroup
        v-bind="$attrs"
        :modelValue="modelValue"
        :name="name"
        :invalid="invalid"
        @update:modelValue="emit('update:modelValue', $event)"
    >
        <slot />
    </PrimeCheckboxGroup>
</template>

