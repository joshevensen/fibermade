<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Colorways">
        <PageHeader
            heading="Colorways"
            :icon="IconList.Colorways"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Colorway"
                    @click="router.visit(createColorway.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
