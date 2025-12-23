<script setup lang="ts">
import PrimeBreadcrumb from 'primevue/breadcrumb';

interface Props {
    model?: any[];
    home?: any;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeBreadcrumb v-bind="$attrs" :model="model" :home="home">
        <slot />
    </PrimeBreadcrumb>
</template>

