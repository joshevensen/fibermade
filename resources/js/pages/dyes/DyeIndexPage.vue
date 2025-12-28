<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import { edit as editDye, destroy as destroyDye } from '@/actions/App/Http/Controllers/DyeController';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    dyes: Array<{
        id: number;
        name: string;
        notes?: string | null;
        does_bleed: boolean;
        do_like: boolean;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const confirm = useConfirm();
const { openDrawer } = useCreateDrawer();

function formatBoolean(value: boolean | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }
    return value ? 'Yes' : 'No';
}

function handleDelete(dye: Props['dyes'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${dye.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyDye.url(dye.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    {
        field: 'does_bleed',
        header: 'Does Bleed',
        sortable: true,
        columnKey: 'does_bleed',
        bodyTemplate: (data: Props['dyes'][0]) => formatBoolean(data.does_bleed),
    },
    {
        field: 'do_like',
        header: 'Do Like',
        sortable: true,
        columnKey: 'do_like',
        bodyTemplate: (data: Props['dyes'][0]) => formatBoolean(data.do_like),
    },
]);
</script>

<template>
    <AppLayout page-title="Dyes">
        <PageHeader
            heading="Dyes"
            :icon="IconList.Dyes"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('dye')"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiCard>
                <template #content>
                    <UiDataTable
                        :value="dyes"
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
                                @click="router.visit(editDye.url(data.id))"
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
