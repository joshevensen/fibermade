<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import PrimeColumn from 'primevue/column';
import { create as createDye, edit as editDye, destroy as destroyDye } from '@/actions/App/Http/Controllers/DyeController';
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
    { field: 'notes', header: 'Notes', sortable: true, columnKey: 'notes' },
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
                    :icon="IconList.Plus"
                    size="small"
                    label="Dye"
                    @click="router.visit(createDye.url())"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataTable
                :value="dyes"
                :columns="columns"
                data-key="id"
                striped-rows
                show-gridlines
            >
                <PrimeColumn field="does_bleed" header="Does Bleed" sortable columnKey="does_bleed">
                    <template #body="{ data }">
                        {{ formatBoolean(data.does_bleed) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn field="do_like" header="Do Like" sortable columnKey="do_like">
                    <template #body="{ data }">
                        {{ formatBoolean(data.do_like) }}
                    </template>
                </PrimeColumn>

                <PrimeColumn header="Actions" :exportable="false" style="width: 8rem" columnKey="actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
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
                        </div>
                    </template>
                </PrimeColumn>
            </UiDataTable>
        </div>
    </AppLayout>
</template>
