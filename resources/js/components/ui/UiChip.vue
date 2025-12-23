<script setup lang="ts">
import PrimeChip from 'primevue/chip';

interface Props {
    label?: string | number;
    icon?: string;
    image?: string;
    removable?: boolean;
    removeIcon?: string;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeChip
        v-bind="$attrs"
        :label="label"
        :icon="icon"
        :image="image"
        :removable="removable"
        :removeIcon="removeIcon"
    >
        <slot />
    </PrimeChip>
</template>

