<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import PrimeColumn from 'primevue/column';
import { create as createCollection, edit as editCollection, destroy as destroyCollection } from '@/actions/App/Http/Controllers/CollectionController';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    collections: Array<{
        id: number;
        name: string;
        slug: string;
        description?: string | null;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const confirm = useConfirm();

function handleDelete(collection: Props['collections'][0], event: Event) {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${collection.name}?`,
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(destroyCollection.url(collection.id));
        },
    });
}

const columns = computed(() => [
    { field: 'name', header: 'Name', sortable: true, columnKey: 'name' },
    { field: 'slug', header: 'Slug', sortable: true, columnKey: 'slug' },
    { field: 'description', header: 'Description', sortable: true, columnKey: 'description' },
]);
</script>

<template>
    <AppLayout page-title="Collections">
        <PageHeader
            heading="Collections"
            :icon="IconList.Collections"
        >
            <template #actions>
                <UiButton
                    :icon="IconList.Plus"
                    size="small"
                    label="Collection"
                    @click="router.visit(createCollection.url())"
                />
            </template>
        </PageHeader>

        <div class="mt-6">
            <UiDataTable
                :value="collections"
                :columns="columns"
                data-key="id"
                striped-rows
                show-gridlines
            >
                <PrimeColumn header="Actions" :exportable="false" style="width: 8rem" columnKey="actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <UiButton
                                :icon="IconList.Settings"
                                text
                                size="small"
                                @click="router.visit(editCollection.url(data.id))"
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
