<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import PrimeColumn from 'primevue/column';
import { create as createColorway, edit as editColorway, destroy as destroyColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    colorways: Array<{
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        technique?: string | null;
        colors?: string[] | null;
        status: string;
        shopify_product_id?: string | null;
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

function formatColors(colors: string[] | null | undefined): string {
    if (!colors || colors.length === 0) {
        return '';
    }
    return colors.map(color => formatEnum(color)).join(', ');
}

function handleDelete(colorway: Props['colorways'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${colorway.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyColorway.url(colorway.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'slug', header: 'Slug', sortable: true, columnKey: 'slug' },
    { field: 'description', header: 'Description', sortable: true, columnKey: 'description' },
    { field: 'shopify_product_id', header: 'Shopify Product ID', sortable: true, columnKey: 'shopify_product_id' },
]);
</script>

<template>
    <AppLayout page-title="Colorways">
        <PageHeader
            heading="Colorways"
            :icon="IconList.Colorways"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Colorway"
                    @click="router.visit(createColorway.url())"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataTable
                :value="colorways"
                :columns="columns"
                data-key="id"
                striped-rows
                show-gridlines
            >
                <PrimeColumn field="technique" header="Technique" sortable columnKey="technique">
                    <template #body="{ data }">
                        {{ formatEnum(data.technique) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="colors" header="Colors" sortable columnKey="colors">
                    <template #body="{ data }">
                        {{ formatColors(data.colors) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="status" header="Status" sortable columnKey="status">
                    <template #body="{ data }">
                        {{ formatEnum(data.status) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn header="Actions" :exportable="false" style="width: 8rem" columnKey="actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <UiButton
                                :icon="IconList.Settings"
                                text
                                size="small"
                                @click="router.visit(editColorway.url(data.id))"
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
