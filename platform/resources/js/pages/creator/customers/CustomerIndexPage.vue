<script setup lang="ts">
import { edit as editCustomer } from '@/actions/App/Http/Controllers/CustomerController';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataTable from '@/components/ui/UiDataTable.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Customer {
    id: number;
    name: string;
    email?: string | null;
    city?: string | null;
    state?: string | null;
}

interface Props {
    customers: Customer[];
}

const props = defineProps<Props>();

const columns = computed(() => [
    {
        field: 'name',
        header: 'Name',
        sortable: true,
        columnKey: 'name',
    },
    {
        field: 'email',
        header: 'Email',
        sortable: true,
        columnKey: 'email',
    },
    {
        field: 'city',
        header: 'City',
        sortable: true,
        columnKey: 'city',
    },
    {
        field: 'state',
        header: 'State',
        sortable: true,
        columnKey: 'state',
    },
]);
</script>

<template>
    <CreatorLayout page-title="Customers">
        <UiCard>
            <template #title>
                <PageFilter :count="props.customers.length" label="customer" />
            </template>

            <template #content>
                <UiDataTable
                    :value="customers"
                    :columns="columns"
                    data-key="id"
                    paginator
                    :rows="20"
                    striped-rows
                    show-gridlines
                >
                    <template #name="{ data }">
                        <button
                            type="button"
                            class="cursor-pointer text-primary hover:underline"
                            @click="router.visit(editCustomer.url(data.id))"
                        >
                            {{ data.name || '—' }}
                        </button>
                    </template>
                </UiDataTable>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
