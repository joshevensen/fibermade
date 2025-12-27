<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import PrimeColumn from 'primevue/column';
import { create as createDiscount, edit as editDiscount, destroy as destroyDiscount } from '@/actions/App/Http/Controllers/DiscountController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    discounts: Array<{
        id: number;
        name: string;
        type: string;
        code: string;
        parameters?: Record<string, any> | null;
        starts_at?: string | null;
        ends_at?: string | null;
        is_active: boolean;
        shopify_discount_id?: string | null;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const confirm = useConfirm();

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleDateString();
}

function formatBoolean(value: boolean | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return value ? 'Yes' : 'No';
}

function formatParameters(parameters: Record<string, any> | null | undefined): string {
    if (!parameters || Object.keys(parameters).length === 0) {
        return '';
    }
    return JSON.stringify(parameters);
}

function handleDelete(discount: Props['discounts'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${discount.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyDiscount.url(discount.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'code', header: 'Code', sortable: true, columnKey: 'code' },
    { field: 'shopify_discount_id', header: 'Shopify Discount ID', sortable: true, columnKey: 'shopify_discount_id' },
]);
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

        <div class="mt-6">
            <UiDataTable
                :value="discounts"
                :columns="columns"
                data-key="id"
                striped-rows
                show-gridlines
            >
                <PrimeColumn field="type" header="Type" sortable columnKey="type">
                    <template #body="{ data }">
                        {{ formatEnum(data.type) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="parameters" header="Parameters" sortable columnKey="parameters">
                    <template #body="{ data }">
                        {{ formatParameters(data.parameters) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="starts_at" header="Starts At" sortable columnKey="starts_at">
                    <template #body="{ data }">
                        {{ formatDate(data.starts_at) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="ends_at" header="Ends At" sortable columnKey="ends_at">
                    <template #body="{ data }">
                        {{ formatDate(data.ends_at) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="is_active" header="Is Active" sortable columnKey="is_active">
                    <template #body="{ data }">
                        {{ formatBoolean(data.is_active) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn header="Actions" :exportable="false" style="width: 8rem" columnKey="actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <UiButton
                                :icon="IconList.Settings"
                                text
                                size="small"
                                @click="router.visit(editDiscount.url(data.id))"
                            />
                            <UiButton
                                :icon="IconList.Close"
                                text
                                size="small"
                                severity="danger"
                                @click="handleDelete(data, $event)"
                            />
                        </div>
                    </template>
                </PrimeColumn>
            </UiDataTable>
        </div>
    </AppLayout>
</template>
