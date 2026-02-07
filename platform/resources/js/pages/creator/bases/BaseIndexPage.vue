<script setup lang="ts">
import {
    edit as editBase,
    index as indexBase,
} from '@/actions/App/Http/Controllers/BaseController';
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
    bases: Array<{
        id: number;
        description?: string | null;
        status: string;
        weight?: string | null;
        descriptor: string;
        size?: number | null;
        cost?: number | null;
        retail_price?: number | null;
        wool_percent?: number | null;
        nylon_percent?: number | null;
        alpaca_percent?: number | null;
        yak_percent?: number | null;
        camel_percent?: number | null;
        cotton_percent?: number | null;
        bamboo_percent?: number | null;
    }>;
    totalBases?: number;
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
    { label: 'Retired', value: 'retired' },
];

function handleStatusFilterChange(value: string): void {
    statusFilter.value = value;
    router.get(
        indexBase.url({ query: { status: value } }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['bases'],
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

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function handleCardClick(base: Props['bases'][0]): void {
    router.visit(editBase.url(base.id));
}

function getGridItemProps(base: Props['bases'][0]) {
    const metadata: Array<{ label: string; value: string | number | null }> =
        [];
    if (base.weight) {
        metadata.push({
            label: 'Weight',
            value: formatEnum(base.weight),
        });
    }
    if (base.size !== null && base.size !== undefined) {
        metadata.push({
            label: 'Size',
            value: `${base.size}g`,
        });
    }
    if (base.retail_price !== null && base.retail_price !== undefined) {
        metadata.push({
            label: 'Retail Price',
            value: formatCurrency(base.retail_price),
        });
    }

    const severity: 'success' | 'secondary' =
        base.status === 'active' ? 'success' : 'secondary';

    return {
        title: base.descriptor,
        tag: {
            severity,
            value: formatEnum(base.status),
        },
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}
</script>

<template>
    <CreatorLayout page-title="Bases">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.totalBases ?? bases.length"
                    :filtered-count="bases.length"
                    label="base"
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
                    :value="bases"
                    layout="grid"
                    data-key="id"
                    paginator
                    :rows="12"
                    empty-message="No bases found"
                >
                    <template #grid="{ items }">
                        <GridItemWrapper>
                            <GridItem
                                v-for="base in items"
                                :key="base.id"
                                v-bind="getGridItemProps(base)"
                                @click="handleCardClick(base)"
                            />
                        </GridItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
