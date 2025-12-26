<script setup lang="ts">
interface Props {
    heading: string;
    icon?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-2 h-10 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i v-if="icon" :class="icon" class="text-lg text-surface-400" />
            <h1 class="text-xl font-bold text-surface-500">{{ heading }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>

