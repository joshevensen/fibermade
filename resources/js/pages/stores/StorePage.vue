<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Stores">
        <PageHeader
            heading="Stores"
            :icon="IconList.Stores"
        />
    </AppLayout>
</template>

