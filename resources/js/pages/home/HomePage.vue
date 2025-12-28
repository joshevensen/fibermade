<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';

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
