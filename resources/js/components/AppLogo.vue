<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    variant?: 'full' | 'icon';
    className?: HTMLAttributes['class'];
}

withDefaults(defineProps<Props>(), {
    variant: 'full',
});
</script>

<template>
    <template v-if="variant === 'icon'">
        <img
            src="/icon.png"
            alt="App Icon"
            :class="className"
            v-bind="$attrs"
        />
    </template>
    <template v-else>
        <img
            src="/logo.png"
            alt="App Logo"
            :class="className"
            v-bind="$attrs"
        />
    </template>
</template>
