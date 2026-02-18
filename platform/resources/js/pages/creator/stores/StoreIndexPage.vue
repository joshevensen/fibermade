<script setup lang="ts">
import { resend as resendInvite } from '@/actions/App/Http/Controllers/InviteController';
import { edit as editStore } from '@/actions/App/Http/Controllers/StoreController';
import GridItem from '@/components/GridItem.vue';
import GridItemWrapper from '@/components/GridItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import { useToast } from '@/composables/useToast';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { index as storesIndex } from '@/routes/stores';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface StoreOrInviteItem {
    id: number;
    list_key: string;
    item_type: 'store' | 'invite';
    name: string;
    email: string;
    owner_name?: string | null;
    address_line1: string;
    address_line2?: string | null;
    city: string;
    state_region: string;
    postal_code: string;
    country_code: string;
    status: string;
    is_invited: boolean;
    invite_id: number | null;
    discount_rate?: number | null;
    payment_terms?: string | null;
}

interface Props {
    stores: StoreOrInviteItem[];
    totalStores?: number;
    filteredCount?: number;
}

const props = defineProps<Props>();

const getInitialStatusFilter = (): string => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('status') ?? 'active';
    }
    return 'active';
};

const statusFilter = ref<string>(getInitialStatusFilter());
const resendingInviteId = ref<number | null>(null);
const { showSuccess } = useToast();

const statusOptions = [
    { label: 'All', value: 'all' },
    { label: 'Invited', value: 'invited' },
    { label: 'Active', value: 'active' },
    { label: 'Paused', value: 'paused' },
    { label: 'Ended', value: 'ended' },
];

function handleStatusFilterChange(value: string): void {
    statusFilter.value = value;
    router.get(
        storesIndex.url(),
        { status: value },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['stores', 'totalStores', 'filteredCount'],
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
        case 'invited':
            return 'info';
        case 'paused':
            return 'warn';
        case 'ended':
            return 'secondary';
        default:
            return 'secondary';
    }
}

function handleCardClick(item: StoreOrInviteItem): void {
    if (item.item_type !== 'store') {
        return;
    }
    router.visit(editStore.url(item.id));
}

function handleResend(item: StoreOrInviteItem): void {
    if (item.item_type !== 'invite' || item.invite_id == null) {
        return;
    }
    const { url } = resendInvite.post(item.invite_id);
    resendingInviteId.value = item.invite_id;
    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccess('Invite resent.');
            },
            onFinish: () => {
                resendingInviteId.value = null;
            },
        },
    );
}

function getGridItemProps(item: StoreOrInviteItem) {
    const metadata: Array<{ label: string; value: string | null }> = [];
    if (item.email) {
        metadata.push({
            label: 'Email',
            value: item.email,
        });
    }
    if (item.owner_name) {
        metadata.push({
            label: 'Owner',
            value: item.owner_name,
        });
    }
    if (item.city && item.state_region) {
        metadata.push({
            label: 'Location',
            value: `${item.city}, ${item.state_region}`,
        });
    }
    if (item.item_type === 'store' && item.discount_rate != null) {
        const rate = Number(item.discount_rate);
        const pct = rate <= 1 ? rate * 100 : rate;
        metadata.push({
            label: 'Discount',
            value: `${pct}%`,
        });
    }
    if (item.item_type === 'store' && item.payment_terms) {
        metadata.push({
            label: 'Terms',
            value: item.payment_terms,
        });
    }

    return {
        title: item.name,
        tag: {
            severity: getStatusSeverity(item.status),
            value: formatEnum(item.status),
        },
        metadata: metadata.length > 0 ? metadata : undefined,
        clickable: item.item_type === 'store',
    };
}
</script>

<template>
    <CreatorLayout page-title="Stores">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.totalStores ?? stores.length"
                    :filtered-count="props.filteredCount ?? stores.length"
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
                    data-key="list_key"
                    paginator
                    :rows="12"
                    empty-message="No stores found"
                >
                    <template #grid="{ items }">
                        <GridItemWrapper>
                            <GridItem
                                v-for="item in items"
                                :key="item.list_key"
                                v-bind="getGridItemProps(item)"
                                @click="handleCardClick(item)"
                            >
                                <template v-if="item.is_invited" #actions>
                                    <UiButton
                                        size="small"
                                        severity="secondary"
                                        :loading="
                                            item.invite_id != null &&
                                            resendingInviteId === item.invite_id
                                        "
                                        :disabled="resendingInviteId != null"
                                        @click.stop="handleResend(item)"
                                    >
                                        Resend
                                    </UiButton>
                                </template>
                            </GridItem>
                        </GridItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
