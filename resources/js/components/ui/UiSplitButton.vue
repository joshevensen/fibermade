<script lang="ts">
import type { SplitButtonTokenSections } from '@primeuix/themes/types/splitbutton';

export const splitButtonRoot: SplitButtonTokenSections.Root = {
    borderRadius: '{form.field.border.radius}',
    roundedBorderRadius: '2rem',
    raisedShadow: '0 3px 1px -2px rgba(0, 0, 0, 0.2), 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12)'
};
</script>

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

