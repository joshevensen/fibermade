<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';

const { BusinessIconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Inventory">
        <PageHeader
            heading="Inventory"
            :business-icon="BusinessIconList.Inventory"
        />
    </AppLayout>
</template>
