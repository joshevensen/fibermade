<script setup lang="ts">
import { edit as editShow } from '@/actions/App/Http/Controllers/ShowController';
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
    location_address?: string | null;
    location_city?: string | null;
    location_state?: string | null;
    location_zip?: string | null;
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
</script>

<template>
    <AppLayout page-title="Shows">
        <UiCard>
            <template #title>
                <div
                    class="flex flex-wrap items-center justify-between gap-4 p-4 pb-0"
                >
                    <div class="text-surface-600">
                        <template
                            v-if="
                                filteredAndSortedShows.length !==
                                props.shows.length
                            "
                        >
                            {{ filteredAndSortedShows.length }} of
                            {{ props.shows.length }}
                        </template>
                        <template v-else>
                            {{ filteredAndSortedShows.length }}
                        </template>
                        {{
                            filteredAndSortedShows.length === 1
                                ? 'show'
                                : 'shows'
                        }}
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <UiFormFieldSelect
                            name="date-filter"
                            label="Date"
                            label-position="left"
                            :options="dateFilterOptions"
                            option-label="label"
                            option-value="value"
                            :initial-value="dateFilter"
                            :validate-on-mount="false"
                            :validate-on-blur="false"
                            :validate-on-submit="false"
                            :validate-on-value-update="true"
                            size="small"
                            class="w-40"
                            @update:model-value="dateFilter = $event"
                        />
                    </div>
                </div>
            </template>

            <template #content>
                <UiDataView
                    :value="filteredAndSortedShows"
                    layout="list"
                    data-key="id"
                    paginator
                    :rows="20"
                >
                    <template #list="{ items }">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="show in items"
                                :key="show.id"
                                class="flex cursor-pointer items-center justify-between rounded-lg border border-surface-200 p-4 transition-colors hover:bg-surface-50"
                                @click="handleShowClick(show)"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-surface-900">
                                        {{ show.name }}
                                    </div>
                                    <div
                                        class="mt-1 flex gap-4 text-sm text-surface-600"
                                    >
                                        <span>
                                            {{
                                                formatDateRange(
                                                    show.start_at,
                                                    show.end_at,
                                                )
                                            }}
                                        </span>
                                        <span v-if="show.location_name">
                                            {{ show.location_name }}
                                        </span>
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
                                No shows found
                            </p>
                        </div>
                    </template>
                </UiDataView>
            </template>
        </UiCard>
    </AppLayout>
</template>
