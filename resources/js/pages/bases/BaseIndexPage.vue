<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createBase } from '@/actions/App/Http/Controllers/BaseController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Bases">
        <PageHeader
            heading="Bases"
            :icon="IconList.Bases"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Base"
                    @click="router.visit(createBase.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
