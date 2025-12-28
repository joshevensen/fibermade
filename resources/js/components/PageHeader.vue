<script setup lang="ts">
import UiIcon from '@/components/ui/UiIcon.vue';
import type { Component } from 'vue';

interface Props {
    heading: string;
    icon?: string; // Prime Icon CSS class
    businessIcon?: Component; // Tabler Icon component
}

defineProps<Props>();
</script>

<template>
    <div class="mb-2 h-10 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <UiIcon
                v-if="icon"
                :name="icon"
                class="text-lg text-surface-400"
            />
            <UiIcon
                v-else-if="businessIcon"
                :component="businessIcon"
                class="text-lg text-surface-400"
            />
            <h1 class="text-xl font-bold text-surface-500">{{ heading }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>

