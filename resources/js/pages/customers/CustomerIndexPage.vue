<script setup lang="ts">
import { destroy as destroyCustomer } from '@/actions/App/Http/Controllers/CustomerController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiPanel from '@/components/ui/UiPanel.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, reactive } from 'vue';

interface Customer {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    city?: string | null;
    state?: string | null;
    zip?: string | null;
    notes?: string | null;
}

interface Props {
    customers: Customer[];
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();
const { requireDelete } = useConfirm();
const { openDrawer } = useCreateDrawer();
const toast = useToast();

// Sort customers
const sortedCustomers = computed(() => {
    return [...props.customers].sort((a, b) => a.name.localeCompare(b.name));
});

// Track panel expanded state per customer
const expandedPanels = reactive<Record<number, boolean>>({});

// Track notes editing state per customer
const editingNotes = reactive<Record<number, string>>({});
const savingNotes = reactive<Record<number, boolean>>({});

// Initialize notes editing state
props.customers.forEach((customer) => {
    editingNotes[customer.id] = customer.notes || '';
});

function togglePanel(customerId: number): void {
    expandedPanels[customerId] = !expandedPanels[customerId];
}

function handleSaveNotes(customer: Customer): void {
    savingNotes[customer.id] = true;

    router.patch(
        `/customers/${customer.id}/notes`,
        {
            notes: editingNotes[customer.id],
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                savingNotes[customer.id] = false;
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Notes saved successfully',
                    life: 3000,
                });
            },
            onError: () => {
                savingNotes[customer.id] = false;
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to save notes',
                    life: 3000,
                });
            },
        },
    );
}

function handleDelete(customer: Customer, event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${customer.name}?`,
        onAccept: () => {
            router.delete(destroyCustomer.url(customer.id));
        },
    });
}

function formatAddress(customer: Customer): string {
    const parts: string[] = [];
    if (customer.address) {
        parts.push(customer.address);
    }
    if (customer.city || customer.state || customer.zip) {
        const cityStateZip = [customer.city, customer.state, customer.zip]
            .filter(Boolean)
            .join(', ');
        if (cityStateZip) {
            parts.push(cityStateZip);
        }
    }
    return parts.join('\n');
}
</script>

<template>
    <AppLayout page-title="Customers">
        <PageHeader
            heading="Customers"
            :business-icon="BusinessIconList.Customers"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('customer')"
                />
            </template>
        </PageHeader>

        <div class="mt-6 flex flex-col gap-4">
            <UiDataView
                :value="sortedCustomers"
                layout="list"
                data-key="id"
                paginator
                :rows="20"
            >
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm text-surface-600">
                            {{ sortedCustomers.length }}
                            {{
                                sortedCustomers.length === 1
                                    ? 'customer'
                                    : 'customers'
                            }}
                        </div>
                    </div>
                </template>
                <template #list="{ items }">
                    <div class="flex flex-col gap-2">
                        <UiPanel
                            v-for="customer in items"
                            :key="customer.id"
                            :toggleable="true"
                            :collapsed="!expandedPanels[customer.id]"
                            @toggle="togglePanel(customer.id)"
                        >
                            <template #header>
                                <div
                                    class="flex w-full items-center justify-between gap-4 pr-3"
                                >
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{
                                            customer.name
                                        }}</span>
                                        <div
                                            v-if="
                                                customer.email || customer.phone
                                            "
                                            class="flex flex-col gap-1 text-sm text-surface-500"
                                        >
                                            <span v-if="customer.email">{{
                                                customer.email
                                            }}</span>
                                            <span v-if="customer.phone">{{
                                                customer.phone
                                            }}</span>
                                        </div>
                                        <span
                                            v-if="formatAddress(customer)"
                                            class="mt-1 text-sm whitespace-pre-line text-surface-500"
                                        >
                                            {{ formatAddress(customer) }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div class="flex flex-col gap-4 pt-4">
                                <div class="flex flex-col gap-2">
                                    <label
                                        class="text-sm font-medium text-surface-700"
                                        >Notes</label
                                    >
                                    <UiEditor
                                        v-model="editingNotes[customer.id]"
                                        placeholder="Add notes about this customer..."
                                    />
                                    <UiButton
                                        label="Save Notes"
                                        :loading="savingNotes[customer.id]"
                                        @click="handleSaveNotes(customer)"
                                    />
                                </div>

                                <UiDivider />

                                <UiButton
                                    label="Delete Customer"
                                    severity="danger"
                                    outlined
                                    @click="handleDelete(customer, $event)"
                                />
                            </div>
                        </UiPanel>
                    </div>
                </template>

                <template #empty>
                    <div class="flex min-h-[60vh] items-center justify-center">
                        <p class="text-lg text-surface-500">
                            No customers found
                        </p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
