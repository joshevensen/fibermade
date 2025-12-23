<script setup lang="ts">
import PrimeEditor from 'primevue/editor';

interface Props {
    modelValue?: string;
    placeholder?: string;
    readonly?: boolean;
    invalid?: boolean;
    formats?: any[];
    modules?: any;
    editorStyle?: any;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeEditor
        v-bind="$attrs"
        :modelValue="modelValue"
        :placeholder="placeholder"
        :readonly="readonly"
        :invalid="invalid"
        :formats="formats"
        :modules="modules"
        :editorStyle="editorStyle"
    >
        <slot />
    </PrimeEditor>
</template>

