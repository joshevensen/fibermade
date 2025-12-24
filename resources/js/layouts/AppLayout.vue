<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmPopup from 'primevue/confirmpopup';
import Toast from 'primevue/toast';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {
});
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />

    <slot />

    <ConfirmPopup />
    <Toast />
</template>
