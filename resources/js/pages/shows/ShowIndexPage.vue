<script setup lang="ts">
import { edit as editShow } from '@/actions/App/Http/Controllers/ShowController';
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import PageFilter from '@/components/PageFilter.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Show {
    id: number;
    name: string;
    start_at: string;
    end_at: string;
    location_name?: string | null;
    address_line1?: string | null;
    city?: string | null;
    state_region?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
    description?: string | null;
    website?: string | null;
}

interface Props {
    shows: Show[];
}

const props = defineProps<Props>();

// Filter state
const dateFilter = ref<string>('All');

const dateFilterOptions = [
    { label: 'All', value: 'All' },
    { label: 'Upcoming', value: 'Upcoming' },
    { label: 'Past', value: 'Past' },
];

// Sort and filter shows
const filteredAndSortedShows = computed(() => {
    let filtered = [...props.shows];
    const now = new Date();

    // Apply date filter
    if (dateFilter.value === 'Upcoming') {
        filtered = filtered.filter((show) => {
            const startDate = new Date(show.start_at);
            return startDate >= now;
        });
    } else if (dateFilter.value === 'Past') {
        filtered = filtered.filter((show) => {
            const endDate = new Date(show.end_at);
            return endDate < now;
        });
    }

    // Sort by start_at date (upcoming first, then past)
    return filtered.sort((a, b) => {
        const dateA = new Date(a.start_at).getTime();
        const dateB = new Date(b.start_at).getTime();
        return dateB - dateA; // Most recent first
    });
});

function formatDateRange(start: string, end: string): string {
    const startDate = new Date(start);
    const endDate = new Date(end);

    // Reset time to compare dates only
    const startDateOnly = new Date(
        startDate.getFullYear(),
        startDate.getMonth(),
        startDate.getDate(),
    );
    const endDateOnly = new Date(
        endDate.getFullYear(),
        endDate.getMonth(),
        endDate.getDate(),
    );

    const startFormatted = startDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    // If same date, just return start date
    if (startDateOnly.getTime() === endDateOnly.getTime()) {
        return startFormatted;
    }

    const endFormatted = endDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    return `${startFormatted} - ${endFormatted}`;
}

function handleShowClick(show: Show): void {
    router.visit(editShow.url(show.id));
}

function getListItemProps(show: Show) {
    const metadata: string[] = [];
    metadata.push(formatDateRange(show.start_at, show.end_at));
    if (show.location_name) {
        metadata.push(show.location_name);
    }

    return {
        title: show.name,
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}
</script>

<template>
    <AppLayout page-title="Shows">
        <UiCard>
            <template #title>
                <PageFilter
                    :count="props.shows.length"
                    :filtered-count="filteredAndSortedShows.length"
                    label="show"
                >
                    <template #filters>
                        <UiFormFieldSelect
                            name="date-filter"
                            label="Date"
                            label-position="left"
                            :options="dateFilterOptions"
                            :initial-value="dateFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-40"
                            @update:model-value="dateFilter = $event"
                        />
                    </template>
                </PageFilter>
            </template>

            <template #content>
                <UiDataView
                    :value="filteredAndSortedShows"
                    layout="list"
                    data-key="id"
                    paginator
                    :rows="20"
                    empty-message="No shows found"
                >
                    <template #list="{ items }">
                        <ListItemWrapper>
                            <ListItem
                                v-for="show in items"
                                :key="show.id"
                                v-bind="getListItemProps(show)"
                                @click="handleShowClick(show)"
                            />
                        </ListItemWrapper>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </AppLayout>
</template>
