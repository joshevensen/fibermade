<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createDye } from '@/actions/App/Http/Controllers/DyeController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Dyes">
        <PageHeader
            heading="Dyes"
            :icon="IconList.Dyes"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Dye"
                    @click="router.visit(createDye.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
