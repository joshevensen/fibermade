<script setup lang="ts">
import PrimeMenubar from 'primevue/menubar';

interface MenuItem {
    label?: string;
    icon?: string;
    command?: () => void;
    url?: string;
    items?: MenuItem[];
    disabled?: boolean;
    separator?: boolean;
    badge?: string;
    shortcut?: string;
    target?: string;
    [key: string]: any;
}

interface Props {
    model?: MenuItem[];
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeMenubar v-bind="$attrs" :model="model">
        <template #start>
            <slot name="start" />
        </template>
        <template #item="slotProps">
            <slot name="item" v-bind="slotProps" />
        </template>
        <template #end>
            <slot name="end" />
        </template>
    </PrimeMenubar>
</template>

