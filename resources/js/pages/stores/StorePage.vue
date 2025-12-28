<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';

const { BusinessIconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Stores">
        <PageHeader
            heading="Stores"
            :business-icon="BusinessIconList.Stores"
        />
    </AppLayout>
</template>

