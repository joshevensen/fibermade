<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { create as createDiscount } from '@/actions/App/Http/Controllers/DiscountController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Discounts">
        <PageHeader
            heading="Discounts"
            :icon="IconList.Discounts"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Discount"
                    @click="router.visit(createDiscount.url())"
                />
            </template>
        </PageHeader>
    </AppLayout>
</template>
