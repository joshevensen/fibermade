<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <div
        class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >
        <slot />
    </div>
</template>
