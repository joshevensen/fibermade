<script setup lang="ts">
import { edit as editDye } from '@/actions/App/Http/Controllers/DyeController';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
            filtered = filtered.filter(
                (dye) =>
                    dye.manufacturer &&
                    dye.manufacturer !== 'Dharma' &&
                    dye.manufacturer !== 'Jacquard',
            );
        } else {
            filtered = filtered.filter(
                (dye) => dye.manufacturer === manufacturerFilter.value,
            );
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

function handleDyeClick(dye: Dye): void {
    router.visit(editDye.url(dye.id));
}
</script>

<template>
    <AppLayout page-title="Dyes">
        <UiCard>
            <template #title>
                <div
                    class="flex flex-wrap items-center justify-between gap-4 p-4 pb-0"
                >
                    <div class="text-surface-600">
                        <template
                            v-if="
                                filteredAndSortedDyes.length !==
                                props.dyes.length
                            "
                        >
                            {{ filteredAndSortedDyes.length }} of
                            {{ props.dyes.length }}
                        </template>
                        <template v-else>
                            {{ filteredAndSortedDyes.length }}
                        </template>
                        {{
                            filteredAndSortedDyes.length === 1 ? 'dye' : 'dyes'
                        }}
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
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

            <template #content>
                <UiDataView
                    :value="filteredAndSortedDyes"
                    layout="list"
                    data-key="id"
                    paginator
                    :rows="20"
                >
                    <template #list="{ items }">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="dye in items"
                                :key="dye.id"
                                class="flex cursor-pointer items-center gap-4 rounded-lg border border-surface-200 p-2 pr-4 transition-colors hover:bg-surface-50"
                                @click="handleDyeClick(dye)"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-surface-900">
                                        {{ dye.name }}
                                    </div>
                                    <div
                                        class="mt-1 flex gap-4 text-sm text-surface-600"
                                    >
                                        <span v-if="dye.manufacturer">
                                            {{ dye.manufacturer }}
                                        </span>
                                        <span v-if="dye.does_bleed"
                                            >Bleeds</span
                                        >
                                        <span v-if="!dye.do_like"
                                            >Don't Like</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div
                            class="flex min-h-[60vh] items-center justify-center"
                        >
                            <p class="text-lg text-surface-500">
                                No dyes found
                            </p>
                        </div>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </AppLayout>
</template>
