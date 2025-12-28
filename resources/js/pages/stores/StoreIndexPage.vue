<script setup lang="ts">
import { edit as editStore } from '@/actions/App/Http/Controllers/StoreController';
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
    stores: Array<{
        id: number;
        name: string;
        email: string;
        owner_name?: string | null;
        address_line_1: string;
        address_line_2?: string | null;
        city: string;
        state: string;
        zip: string;
        country: string;
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

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'active':
            return 'success';
        case 'paused':
            return 'warning';
        case 'ended':
            return 'secondary';
        default:
            return 'secondary';
    }
}

function handleCardClick(store: Props['stores'][0]): void {
    router.visit(editStore.url(store.id));
}
</script>

<template>
    <AppLayout page-title="Stores">
        <PageHeader heading="Stores" :business-icon="BusinessIconList.Stores">
            <template #actions>
                <UiButton
                    size="small"
                    label="Store"
                    @click="openDrawer('store')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataView
                :value="stores"
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
                                    props.totalStores &&
                                    stores.length !== props.totalStores
                                "
                            >
                                {{ stores.length }} of {{ props.totalStores }}
                            </template>
                            <template v-else>
                                {{ stores.length }}
                            </template>
                            {{ stores.length === 1 ? 'store' : 'stores' }}
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
                            v-for="store in items"
                            :key="store.id"
                            class="cursor-pointer rounded-lg border border-surface-200 bg-surface-0 p-4 transition-all hover:border-primary-500 hover:shadow-md"
                            @click="handleCardClick(store)"
                        >
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-start">
                                    <UiTag
                                        :severity="
                                            getStatusSeverity(store.status)
                                        "
                                        :value="formatEnum(store.status)"
                                    />
                                </div>
                                <h3
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ store.name }}
                                </h3>

                                <div
                                    class="mt-2 flex flex-col gap-1 border-t border-surface-200 pt-2"
                                >
                                    <div
                                        v-if="store.email"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Email:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{ store.email }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="store.owner_name"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Owner:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{ store.owner_name }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="store.city && store.state"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-surface-500"
                                            >Location:</span
                                        >
                                        <span
                                            class="font-medium text-surface-900"
                                            >{{ store.city }},
                                            {{ store.state }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="flex min-h-[60vh] items-center justify-center">
                        <p class="text-lg text-surface-500">No stores found</p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
