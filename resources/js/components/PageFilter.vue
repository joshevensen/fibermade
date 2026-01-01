<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    count: number;
    filteredCount?: number;
    label: string;
}

const props = withDefaults(defineProps<Props>(), {
    filteredCount: undefined,
});

const actualFilteredCount = computed(() => {
    return props.filteredCount ?? props.count;
});

const displayCount = computed(() => {
    return actualFilteredCount.value !== props.count
        ? `${actualFilteredCount.value} of ${props.count}`
        : props.count.toString();
});

const displayLabel = computed(() => {
    return actualFilteredCount.value === 1 ? props.label : props.label + 's';
});
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Count display -->
        <div>
            <p class="text-base text-surface-600">
                {{ displayCount }} {{ displayLabel }}
            </p>
        </div>

        <!-- Filters and toggle -->
        <div class="flex flex-wrap items-center gap-4">
            <slot name="filters" />
            <slot name="toggle" />
        </div>
    </div>
</template>
