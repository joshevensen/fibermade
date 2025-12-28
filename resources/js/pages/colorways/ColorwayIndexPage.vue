<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import { edit as editColorway, destroy as destroyColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
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
const { IconList, BusinessIconList } = useIcon();
const confirm = useConfirm();
const { openDrawer } = useCreateDrawer();

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
        icon: IconList.ExclamationTriangle,
        accept: () => {
            router.delete(destroyColorway.url(colorway.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    {
        field: 'technique',
        header: 'Technique',
        sortable: true,
        columnKey: 'technique',
        bodyTemplate: (data: Props['colorways'][0]) => formatEnum(data.technique),
    },
    {
        field: 'colors',
        header: 'Colors',
        sortable: true,
        columnKey: 'colors',
        bodyTemplate: (data: Props['colorways'][0]) => formatColors(data.colors),
    },
    {
        field: 'status',
        header: 'Status',
        sortable: true,
        columnKey: 'status',
        bodyTemplate: (data: Props['colorways'][0]) => formatEnum(data.status),
    },
]);
</script>

<template>
    <AppLayout page-title="Colorways">
        <PageHeader
            heading="Colorways"
            :business-icon="BusinessIconList.Colorways"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('colorway')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiCard>
                <template #content>
                    <UiDataTable
                        :value="colorways"
                        :columns="columns"
                        data-key="id"
                        striped-rows
                        show-gridlines
                    >
                        <template #actions="{ data }">
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
                        </template>
                    </UiDataTable>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
