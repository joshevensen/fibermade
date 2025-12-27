<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import PrimeColumn from 'primevue/column';
import { create as createBase, edit as editBase, destroy as destroyBase } from '@/actions/App/Http/Controllers/BaseController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    bases: Array<{
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        status: string;
        weight?: string | null;
        descriptor?: string | null;
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

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

function formatPercent(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return `${value}%`;
}

function handleDelete(base: Props['bases'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${base.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyBase.url(base.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'slug', header: 'Slug', sortable: true, columnKey: 'slug' },
    { field: 'description', header: 'Description', sortable: true, columnKey: 'description' },
    { field: 'descriptor', header: 'Descriptor', sortable: true, columnKey: 'descriptor' },
    { field: 'size', header: 'Size', sortable: true, columnKey: 'size' },
]);
</script>

<template>
    <AppLayout page-title="Bases">
        <PageHeader
            heading="Bases"
            :icon="IconList.Bases"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Base"
                    @click="router.visit(createBase.url())"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataTable
                :value="bases"
                :columns="columns"
                data-key="id"
                striped-rows
                show-gridlines
            >
                <PrimeColumn field="status" header="Status" sortable columnKey="status">
                    <template #body="{ data }">
                        {{ formatEnum(data.status) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="weight" header="Weight" sortable columnKey="weight">
                    <template #body="{ data }">
                        {{ formatEnum(data.weight) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="cost" header="Cost" sortable columnKey="cost">
                    <template #body="{ data }">
                        {{ formatCurrency(data.cost) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="retail_price" header="Retail Price" sortable columnKey="retail_price">
                    <template #body="{ data }">
                        {{ formatCurrency(data.retail_price) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="wool_percent" header="Wool %" sortable columnKey="wool_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.wool_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="nylon_percent" header="Nylon %" sortable columnKey="nylon_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.nylon_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="alpaca_percent" header="Alpaca %" sortable columnKey="alpaca_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.alpaca_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="yak_percent" header="Yak %" sortable columnKey="yak_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.yak_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="camel_percent" header="Camel %" sortable columnKey="camel_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.camel_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="cotton_percent" header="Cotton %" sortable columnKey="cotton_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.cotton_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="bamboo_percent" header="Bamboo %" sortable columnKey="bamboo_percent">
                    <template #body="{ data }">
                        {{ formatPercent(data.bamboo_percent) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn header="Actions" :exportable="false" style="width: 8rem" columnKey="actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <UiButton
                                :icon="IconList.Settings"
                                text
                                size="small"
                                @click="router.visit(editBase.url(data.id))"
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
