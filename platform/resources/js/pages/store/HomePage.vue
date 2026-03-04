<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router } from '@inertiajs/vue3';

interface Creator {
    id: number;
    list_key: string;
    name: string;
    draft_count: number;
    open_count: number;
    delivered_count: number;
}

interface Props {
    creators: Creator[];
    totalCreators?: number;
}

const props = defineProps<Props>();

function handleOrderHistory(creator: Creator): void {
    router.visit(`/store/${creator.id}/orders`);
}

function handleNewOrder(creator: Creator): void {
    router.visit(`/store/${creator.id}/order`);
}
</script>

<template>
    <StoreLayout page-title="Home">
        <UiCard>
            <template #content>
                <div
                    v-if="props.creators.length === 0"
                    class="rounded-lg border border-dashed border-surface-300 py-12 text-center text-sm text-surface-500"
                >
                    No creators found
                </div>

                <div v-else class="flex flex-col gap-4">
                    <div
                        v-for="creator in props.creators"
                        :key="creator.list_key"
                        class="rounded-lg border border-surface-200 bg-surface-0 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ creator.name }}
                                </h3>
                                <!-- Order counts -->
                                <p class="text-sm text-surface-600">
                                    <span class="font-medium">{{
                                        creator.draft_count
                                    }}</span>
                                    draft
                                    <span class="mx-1">·</span>
                                    <span class="font-medium">{{
                                        creator.open_count
                                    }}</span>
                                    open
                                    <span class="mx-1">·</span>
                                    <span class="font-medium">{{
                                        creator.delivered_count
                                    }}</span>
                                    delivered
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <UiButton
                                    label="Order History"
                                    size="large"
                                    outlined
                                    @click="handleOrderHistory(creator)"
                                />
                                <UiButton
                                    label="New Order"
                                    size="large"
                                    @click="handleNewOrder(creator)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </UiCard>
    </StoreLayout>
</template>
