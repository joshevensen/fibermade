<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import type { Component } from 'vue';

interface Props {
    name?: string;
    component?: Component;
    size?: string | number;
    class?: string;
}

const props = defineProps<Props>();
const attrs = useAttrs();

const isPrimeIcon = computed(() => {
    if (props.component) {
        return false;
    }
    return typeof props.name === 'string' && props.name.startsWith('pi pi-');
});

const iconSize = computed(() => props.size || 24);

const iconClass = computed(() => {
    const classValue = (attrs.class as string) || props.class;
    return classValue;
});
</script>

<template>
    <!-- Prime Icon: CSS class string -->
    <i
        v-if="isPrimeIcon && name"
        :class="[iconClass, name]"
    />

    <!-- Tabler Icon: Vue component -->
    <component
        v-else-if="component"
        :is="component"
        :size="iconSize"
        :class="iconClass"
    />
</template>
