<script setup lang="ts">
import { edit as editShow } from '@/actions/App/Http/Controllers/ShowController';
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const now = new Date();

// Separate and sort upcoming shows (ascending by start_at)
const upcomingShows = computed(() => {
    return props.shows
        .filter((show) => {
            const startDate = new Date(show.start_at);
            return startDate >= now;
        })
        .sort((a, b) => {
            const dateA = new Date(a.start_at).getTime();
            const dateB = new Date(b.start_at).getTime();
            return dateA - dateB; // Ascending (earliest first)
        });
});

// Separate and sort past shows (descending by start_at)
const pastShows = computed(() => {
    return props.shows
        .filter((show) => {
            const endDate = new Date(show.end_at);
            return endDate < now;
        })
        .sort((a, b) => {
            const dateA = new Date(a.start_at).getTime();
            const dateB = new Date(b.start_at).getTime();
            return dateB - dateA; // Descending (most recent first)
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
    <CreatorLayout page-title="Shows">
        <div class="flex flex-col gap-6">
            <UiCard>
                <template #title>Upcoming Shows</template>

                <template #content>
                    <UiDataView
                        :value="upcomingShows"
                        layout="list"
                        data-key="id"
                        paginator
                        :rows="20"
                        empty-message="No upcoming shows"
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

            <UiCard>
                <template #title>Past Shows</template>

                <template #content>
                    <UiDataView
                        :value="pastShows"
                        layout="list"
                        data-key="id"
                        paginator
                        :rows="10"
                        empty-message="No past shows"
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
        </div>
    </CreatorLayout>
</template>
