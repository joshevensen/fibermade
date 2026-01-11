<script setup lang="ts">
import { edit as editStore } from '@/actions/App/Http/Controllers/StoreController';
import GridItem from '@/components/GridItem.vue';
import GridItemWrapper from '@/components/GridItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    stores: Array<{
        id: number;
        name: string;
        email: string;
        owner_name?: string | null;
        address_line1: string;
        address_line2?: string | null;
        city: string;
        state_region: string;
        postal_code: string;
        country_code: string;
        discount_rate?: number | null;
        minimum_order_quantity?: number | null;
        minimum_order_value?: number | null;
        payment_terms?: string | null;
        lead_time_days?: number | null;
        allows_preorders: boolean;
        status: string;
        notes?: string | null;
    }>;
    totalStores?: number;
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
        '/stores',
        { status: value },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['stores'],
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

function handleCardClick(store: Props['stores'][0]): void {
    router.visit(editStore.url(store.id));
}

function getGridItemProps(store: Props['stores'][0]) {
    const metadata: Array<{ label: string; value: string | null }> = [];
    if (store.email) {
        metadata.push({
            label: 'Email',
            value: store.email,
        });
    }
    if (store.owner_name) {
        metadata.push({
            label: 'Owner',
            value: store.owner_name,
        });
    }
    if (store.city && store.state_region) {
        metadata.push({
            label: 'Location',
            value: `${store.city}, ${store.state_region}`,
        });
    }

    return {
        title: store.name,
        tag: {
            severity: getStatusSeverity(store.status),
            value: formatEnum(store.status),
        },
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}
</script>

<template>
    <CreatorLayout page-title="Stores">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.totalStores ?? stores.length"
                    :filtered-count="stores.length"
                    label="store"
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
                <UiDataView
                    :value="stores"
                    layout="grid"
                    data-key="id"
                    paginator
                    :rows="12"
                    empty-message="No stores found"
                >
                    <template #grid="{ items }">
                        <GridItemWrapper>
                            <GridItem
                                v-for="store in items"
                                :key="store.id"
                                v-bind="getGridItemProps(store)"
                                @click="handleCardClick(store)"
                            />
                        </GridItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
