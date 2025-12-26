<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createCollection } from '@/actions/App/Http/Controllers/CollectionController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Collections">
        <PageHeader
            heading="Collections"
            :icon="IconList.Collections"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Collection"
                    @click="router.visit(createCollection.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
