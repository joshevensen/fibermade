<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createOrder } from '@/actions/App/Http/Controllers/OrderController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Orders">
        <PageHeader
            heading="Orders"
            :icon="IconList.Orders"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Order"
                    @click="router.visit(createOrder.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
