<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useIcon } from '@/composables/useIcon';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Edit Dye">
        <PageHeader
            heading="Edit Dye"
            :icon="IconList.Dyes"
        />
    </AppLayout>
</template>
