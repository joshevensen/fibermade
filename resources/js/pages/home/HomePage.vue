<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';

const { BusinessIconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Dashboard">
        <PageHeader
            heading="Dashboard"
            :business-icon="BusinessIconList.Dashboard"
        />
    </AppLayout>
</template>
