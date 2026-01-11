<script setup lang="ts">
import { edit as editDye } from '@/actions/App/Http/Controllers/DyeController';
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
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

function getListItemProps(dye: Dye) {
    const metadata: string[] = [];
    if (dye.manufacturer) {
        metadata.push(dye.manufacturer);
    }
    if (dye.does_bleed) {
        metadata.push('Bleeds');
    }
    if (!dye.do_like) {
        metadata.push("Don't Like");
    }

    return {
        title: dye.name,
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}
</script>

<template>
    <CreatorLayout page-title="Dyes">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.dyes.length"
                    :filtered-count="filteredAndSortedDyes.length"
                    label="dye"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="manufacturer-filter"
                            label="Manufacturer"
                            label-position="left"
                            :options="manufacturerOptions"
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
                            :initial-value="likeFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-32"
                            @update:model-value="likeFilter = $event"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <UiDataView
                    :value="filteredAndSortedDyes"
                    layout="list"
                    data-key="id"
                    paginator
                    :rows="20"
                    empty-message="No dyes found"
                >
                    <template #list="{ items }">
                        <ListItemWrapper>
                            <ListItem
                                v-for="dye in items"
                                :key="dye.id"
                                v-bind="getListItemProps(dye)"
                                @click="handleDyeClick(dye)"
                            />
                        </ListItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </CreatorLayout>
</template>
