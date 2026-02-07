<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

interface Props {
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {});
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />

    <slot />

    <Toast />
</template>
