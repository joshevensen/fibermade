<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiPanel from '@/components/ui/UiPanel.vue';
import UiToggleSwitch from '@/components/ui/UiToggleSwitch.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import { destroy as destroyDye } from '@/actions/App/Http/Controllers/DyeController';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import { useConfirm } from '@/composables/useConfirm';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';

interface Dye {
        id: number;
        name: string;
    manufacturer?: string | null;
        notes?: string | null;
        does_bleed: boolean;
        do_like: boolean;
}

interface Props {
    dyes: Dye[];
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();
const { requireDelete } = useConfirm();
const { openDrawer } = useCreateDrawer();
const toast = useToast();

// Filter state
const manufacturerFilter = ref<string>('All');
const bleedsFilter = ref<string>('Both');
const likeFilter = ref<string>('Yes');

const manufacturerOptions = [
    { label: 'All', value: 'All' },
    { label: 'Dharma', value: 'Dharma' },
    { label: 'Jacquard', value: 'Jacquard' },
    { label: 'Other', value: 'Other' },
];

const bleedsOptions = [
    { label: 'Both', value: 'Both' },
    { label: 'Yes', value: 'Yes' },
    { label: 'No', value: 'No' },
];

const likeOptions = [
    { label: 'Both', value: 'Both' },
    { label: 'Yes', value: 'Yes' },
    { label: 'No', value: 'No' },
];

// Sort and filter dyes
const filteredAndSortedDyes = computed(() => {
    let filtered = [...props.dyes];

    // Apply manufacturer filter
    if (manufacturerFilter.value !== 'All') {
        if (manufacturerFilter.value === 'Other') {
            filtered = filtered.filter((dye) => 
                dye.manufacturer && 
                dye.manufacturer !== 'Dharma' && 
                dye.manufacturer !== 'Jacquard'
            );
        } else {
            filtered = filtered.filter((dye) => dye.manufacturer === manufacturerFilter.value);
        }
    }

    // Apply bleeds filter
    if (bleedsFilter.value !== 'Both') {
        const shouldBleed = bleedsFilter.value === 'Yes';
        filtered = filtered.filter((dye) => dye.does_bleed === shouldBleed);
    }

    // Apply like filter
    if (likeFilter.value !== 'Both') {
        const shouldLike = likeFilter.value === 'Yes';
        filtered = filtered.filter((dye) => dye.do_like === shouldLike);
    }

    // Sort alphabetically by name
    return filtered.sort((a, b) => a.name.localeCompare(b.name));
});

// Track panel expanded state per dye
const expandedPanels = reactive<Record<number, boolean>>({});

// Track notes editing state per dye
const editingNotes = reactive<Record<number, string>>({});
const savingNotes = reactive<Record<number, boolean>>({});

// Track toggle loading states
const toggleLoading = reactive<Record<string, boolean>>({});

// Initialize notes editing state
props.dyes.forEach((dye) => {
    editingNotes[dye.id] = dye.notes || '';
});

function togglePanel(dyeId: number): void {
    expandedPanels[dyeId] = !expandedPanels[dyeId];
}

function handleToggleField(dye: Dye, field: 'does_bleed' | 'do_like', value: boolean): void {
    const key = `${dye.id}-${field}`;
    toggleLoading[key] = true;

    debouncedSaveToggle(dye.id, field, value);
}

const debouncedSaveToggle = useDebounceFn((dyeId: number, field: 'does_bleed' | 'do_like', value: boolean) => {
    const key = `${dyeId}-${field}`;
    router.patch(`/dyes/${dyeId}/toggle-field`, {
        field,
        value,
    }, {
        preserveScroll: true,
        only: ['dyes'],
        onSuccess: () => {
            toggleLoading[key] = false;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Dye updated successfully',
                life: 3000,
            });
        },
        onError: () => {
            toggleLoading[key] = false;
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update dye',
                life: 3000,
            });
        },
    });
}, 500);

function handleSaveNotes(dye: Dye): void {
    savingNotes[dye.id] = true;

    router.patch(`/dyes/${dye.id}/notes`, {
        notes: editingNotes[dye.id],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            savingNotes[dye.id] = false;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Notes saved successfully',
                life: 3000,
            });
        },
        onError: () => {
            savingNotes[dye.id] = false;
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to save notes',
                life: 3000,
            });
        },
    });
}

function handleDelete(dye: Dye, event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${dye.name}?`,
        onAccept: () => {
            router.delete(destroyDye.url(dye.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Dyes">
        <PageHeader
            heading="Dyes"
            :business-icon="BusinessIconList.Dyes"
        >
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('dye')"
                />
            </template>
        </PageHeader>

        <div class="mt-6 flex flex-col gap-4">
            <UiDataView
                :value="filteredAndSortedDyes"
                layout="list"
                data-key="id"
                paginator
                :rows="20"
            >
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm text-surface-600">
                            {{ filteredAndSortedDyes.length }} {{ filteredAndSortedDyes.length === 1 ? 'dye' : 'dyes' }}
                        </div>
                        <div class="flex items-center gap-4">
                        <UiFormFieldSelect
                            name="manufacturer-filter"
                            label="Manufacturer"
                            label-position="left"
                            :options="manufacturerOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="manufacturerFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-40"
                            @update:model-value="manufacturerFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="bleeds-filter"
                            label="Bleeds"
                            label-position="left"
                            :options="bleedsOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="bleedsFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-32"
                            @update:model-value="bleedsFilter = $event"
                        />
                        <UiFormFieldSelect
                            name="like-filter"
                            label="Like"
                            label-position="left"
                            :options="likeOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="likeFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                                size="small"
                            class="w-32"
                            @update:model-value="likeFilter = $event"
                        />
                        </div>
                    </div>
                </template>
                <template #list="{ items }">
                    <div class="flex flex-col gap-2">
                        <UiPanel
                            v-for="dye in items"
                            :key="dye.id"
                            :toggleable="true"
                            :collapsed="!expandedPanels[dye.id]"
                            @toggle="togglePanel(dye.id)"
                        >
                            <template #header>
                                <div class="flex items-center justify-between w-full gap-4 pr-3">
                                    <div class="flex flex-col">
                                        <span
                                            v-if="dye.manufacturer"
                                            class="text-sm text-surface-500"
                                        >
                                            {{ dye.manufacturer }}
                                        </span>
                                        <span class="font-semibold">{{ dye.name }}</span>
                                    </div>
                                    <div
                                        class="flex items-center gap-4"
                                        @click.stop
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-surface-600">Bleeds</span>
                                            <UiToggleSwitch
                                                :model-value="dye.does_bleed"
                                                :disabled="toggleLoading[`${dye.id}-does_bleed`]"
                                                @update:model-value="handleToggleField(dye, 'does_bleed', $event)"
                                            />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-surface-600">Like</span>
                                            <UiToggleSwitch
                                                :model-value="dye.do_like"
                                                :disabled="toggleLoading[`${dye.id}-do_like`]"
                                                @update:model-value="handleToggleField(dye, 'do_like', $event)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="flex flex-col gap-4 pt-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-surface-700">Notes</label>
                                    <UiEditor
                                        v-model="editingNotes[dye.id]"
                                        placeholder="Add notes about this dye..."
                            />
                            <UiButton
                                        label="Save Notes"
                                        :loading="savingNotes[dye.id]"
                                        @click="handleSaveNotes(dye)"
                                    />
                                </div>

                                <UiDivider />

                                <UiButton
                                    label="Delete Dye"
                                severity="danger"
                                    outlined
                                    @click="handleDelete(dye, $event)"
                            />
                            </div>
                        </UiPanel>
                    </div>
                        </template>

                <template #empty>
                    <div class="flex items-center justify-center min-h-[60vh]">
                        <p class="text-surface-500 text-lg">No dyes found</p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
