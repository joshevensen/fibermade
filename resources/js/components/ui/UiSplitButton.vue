<script setup lang="ts">
import PrimeSplitButton from 'primevue/splitbutton';

interface Props {
    label?: string;
    icon?: string;
    dropdownIcon?: string;
    model?: any[];
    severity?: 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'danger' | 'help' | 'contrast';
    size?: 'small' | 'large';
    disabled?: boolean;
    outlined?: boolean;
    text?: boolean;
    raised?: boolean;
    rounded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'small',
});

defineOptions({
    inheritAttrs: false,
});

const emit = defineEmits<{
    click: [event: Event];
}>();

function handleClick(event: Event) {
    emit('click', event);
}
</script>

<template>
    <PrimeSplitButton
        v-bind="$attrs"
        :label="label"
        :icon="icon"
        :dropdownIcon="dropdownIcon"
        :model="model"
        :severity="severity"
        :size="size"
        :disabled="disabled"
        :outlined="outlined"
        :text="text"
        :raised="raised"
        :rounded="rounded"
        @click="handleClick"
    />
</template>

