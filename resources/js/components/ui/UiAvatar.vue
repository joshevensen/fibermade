<script setup lang="ts">
import PrimeAvatar from 'primevue/avatar';

interface Props {
    label?: string;
    icon?: string;
    image?: string;
    size?: 'large' | 'normal' | 'xlarge';
    shape?: 'square' | 'circle';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'normal',
    shape: 'square',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeAvatar
        v-bind="$attrs"
        :label="label"
        :icon="icon"
        :image="image"
        :size="size"
        :shape="shape"
    >
        <slot />
    </PrimeAvatar>
</template>

