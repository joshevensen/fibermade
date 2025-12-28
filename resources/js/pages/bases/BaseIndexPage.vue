<script setup lang="ts">
import { edit as editBase } from '@/actions/App/Http/Controllers/BaseController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiTag from '@/components/ui/UiTag.vue';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    bases: Array<{
        id: number;
        slug: string;
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
const { BusinessIconList } = useIcon();
const { openDrawer } = useCreateDrawer();

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
        '/bases',
        { status: value },
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
</script>

<template>
    <AppLayout page-title="Bases">
        <PageHeader heading="Bases" :business-icon="BusinessIconList.Bases">
            <template #actions>
                <UiButton
                    size="small"
                    label="Base"
                    @click="openDrawer('base')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataView
                :value="bases"
                layout="grid"
                data-key="id"
                paginator
                :rows="12"
            >
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm text-surface-600">
                            <template
                                v-if="
                                    props.totalBases &&
                                    bases.length !== props.totalBases
                                "
                            >
                                {{ bases.length }} of {{ props.totalBases }}
                            </template>
                            <template v-else>
                                {{ bases.length }}
                            </template>
                            {{ bases.length === 1 ? 'base' : 'bases' }}
                        </div>
                        <UiFormFieldSelect
                            name="status-filter"
                            label="Status"
                            label-position="left"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="statusFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-32"
                            @update:model-value="handleStatusFilterChange"
                        />
                    </div>
                </template>
                <template #grid="{ items }">
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="base in items"
                            :key="base.id"
                            class="cursor-pointer rounded-lg border border-surface-200 bg-surface-0 p-4 transition-all hover:border-primary-500 hover:shadow-md"
                            @click="handleCardClick(base)"
                        >
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-start">
                                    <UiTag
                                        :severity="
                                            base.status === 'active'
                                                ? 'success'
                                                : 'secondary'
                                        "
                                        :value="formatEnum(base.status)"
                                    />
                                </div>
                                <h3
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ base.descriptor }}
                                </h3>

                                <div
                                    class="mt-2 flex flex-col gap-1 border-t border-surface-200 pt-2"
                                >
                                    <div
                                        v-if="base.weight"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Weight:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{ formatEnum(base.weight) }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="
                                            base.size !== null &&
                                            base.size !== undefined
                                        "
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Size:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{ base.size }}g</span
                                        >
                                    </div>
                                    <div
                                        v-if="
                                            base.retail_price !== null &&
                                            base.retail_price !== undefined
                                        "
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Retail Price:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{
                                                formatCurrency(
                                                    base.retail_price,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="flex min-h-[60vh] items-center justify-center">
                        <p class="text-lg text-surface-500">No bases found</p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
