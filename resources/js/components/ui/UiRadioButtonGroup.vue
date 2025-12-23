<script setup lang="ts">
import PrimeRadioButtonGroup from 'primevue/radiobuttongroup';

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
    <PrimeRadioButtonGroup
        v-bind="$attrs"
        :modelValue="modelValue"
        :name="name"
        :invalid="invalid"
        @update:modelValue="emit('update:modelValue', $event)"
    >
        <slot />
    </PrimeRadioButtonGroup>
</template>

