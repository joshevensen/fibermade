<script setup lang="ts">
import PrimeInputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

interface Props {
    modelValue?: string | null;
    size?: 'small' | 'large';
    invalid?: boolean;
    variant?: 'outlined' | 'filled';
    disabled?: boolean;
    placeholder?: string;
    required?: boolean;
    fluid?: boolean;
    icon?: string;
    iconPos?: 'start' | 'end';
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <IconField v-if="icon">
        <InputIcon v-if="iconPos !== 'end'" :class="icon" />
        <PrimeInputText
            v-bind="$attrs"
            :modelValue="modelValue"
            :size="size"
            :invalid="invalid"
            :variant="variant"
            :disabled="disabled"
            :placeholder="placeholder"
            :required="required"
            :fluid="fluid"
        >
            <slot />
        </PrimeInputText>
        <InputIcon v-if="iconPos === 'end'" :class="icon" />
    </IconField>
    <PrimeInputText
        v-else
        v-bind="$attrs"
        :modelValue="modelValue"
        :size="size"
        :invalid="invalid"
        :variant="variant"
        :disabled="disabled"
        :placeholder="placeholder"
        :required="required"
        :fluid="fluid"
    >
        <slot />
    </PrimeInputText>
</template>

