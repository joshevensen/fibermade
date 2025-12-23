<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    name: string;
    size?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 16,
});

defineOptions({
    inheritAttrs: false,
});

const iconStyle = computed(() => {
    if (typeof props.size === 'number') {
        return { fontSize: `${props.size}px` };
    }
    return { fontSize: props.size };
});
</script>

<template>
    <i :class="name" :style="iconStyle" v-bind="$attrs" />
</template>

