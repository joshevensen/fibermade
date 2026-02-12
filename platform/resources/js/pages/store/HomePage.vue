<script setup lang="ts">
import PageFilter from '@/components/PageFilter.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiTag from '@/components/ui/UiTag.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Creator {
    id: number;
    list_key: string;
    name: string;
    email: string | null;
    city: string | null;
    state_region: string | null;
    status: string;
    draft_count: number;
    open_count: number;
    delivered_count: number;
}

interface Props {
    creators: Creator[];
    totalCreators?: number;
    filteredCount?: number;
}

const props = defineProps<Props>();

const getInitialStatusFilter = (): string => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('status') || 'active';
    }
    return 'active';
};

const statusFilter = ref<string>(getInitialStatusFilter());

const statusOptions = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Paused', value: 'paused' },
    { label: 'Ended', value: 'ended' },
];

function handleStatusFilterChange(value: string): void {
    statusFilter.value = value;
    router.get(
        '/store',
        { status: value },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['creators', 'totalCreators', 'filteredCount'],
        },
    );
}

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function getStatusSeverity(
    status: string,
): 'success' | 'info' | 'secondary' | 'warn' | 'danger' | 'contrast' {
    switch (status) {
        case 'active':
            return 'success';
        case 'paused':
            return 'warn';
        case 'ended':
            return 'secondary';
        default:
            return 'secondary';
    }
}

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
            <template #title>
                <PageFilter
                    :count="props.totalCreators ?? props.creators.length"
                    :filtered-count="
                        props.filteredCount ?? props.creators.length
                    "
                    label="creator"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="status-filter"
                            label="Status"
                            label-position="left"
                            :options="statusOptions"
                            :initial-value="statusFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-32"
                            @update:model-value="handleStatusFilterChange"
                        />
                    </template>
                </PageFilter>
            </template>

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
                        <!-- Header: Name + Status -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-col gap-1">
                                <h3
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ creator.name }}
                                </h3>
                                <p
                                    v-if="creator.email"
                                    class="text-sm text-surface-600"
                                >
                                    {{ creator.email }}
                                </p>
                                <p
                                    v-if="creator.city && creator.state_region"
                                    class="text-sm text-surface-500"
                                >
                                    {{ creator.city }},
                                    {{ creator.state_region }}
                                </p>
                            </div>
                            <UiTag
                                :severity="getStatusSeverity(creator.status)"
                                :value="formatEnum(creator.status)"
                            />
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between border-t border-surface-100 pt-4"
                        >
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

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <UiButton
                                    label="Order History"
                                    size="small"
                                    outlined
                                    @click="handleOrderHistory(creator)"
                                />
                                <UiButton
                                    label="New Order"
                                    size="small"
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
