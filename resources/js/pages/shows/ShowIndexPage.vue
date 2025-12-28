<script setup lang="ts">
import {
    destroy as destroyShow,
    edit as editShow,
} from '@/actions/App/Http/Controllers/ShowController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiPanel from '@/components/ui/UiPanel.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useCreateDrawer } from '@/composables/useCreateDrawer';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

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
const { BusinessIconList } = useIcon();
const { requireDelete } = useConfirm();
const { openDrawer } = useCreateDrawer();

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

// Track panel expanded state per show
const expandedPanels = reactive<Record<number, boolean>>({});

function togglePanel(showId: number): void {
    expandedPanels[showId] = !expandedPanels[showId];
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatDateRange(start: string, end: string): string {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const startFormatted = startDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
    const endFormatted = endDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    if (startDate.toDateString() === endDate.toDateString()) {
        return `${startFormatted}, ${startDate.getFullYear()}`;
    }

    return `${startFormatted} - ${endFormatted}`;
}

function getLocationString(show: Show): string {
    const parts: string[] = [];
    if (show.location_name) {
        parts.push(show.location_name);
    }
    if (show.location_city) {
        parts.push(show.location_city);
    }
    if (show.location_state) {
        parts.push(show.location_state);
    }
    return parts.join(', ');
}

function handleDelete(show: Show, event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${show.name}?`,
        onAccept: () => {
            router.delete(destroyShow.url(show.id));
        },
    });
}

function handleEdit(show: Show): void {
    router.visit(editShow.url(show.id));
}
</script>

<template>
    <AppLayout page-title="Shows">
        <PageHeader heading="Shows" :business-icon="BusinessIconList.Shows">
            <template #actions>
                <UiButton
                    size="small"
                    label="Create"
                    @click="openDrawer('show')"
                />
            </template>
        </PageHeader>

        <div class="mt-6 flex flex-col gap-4">
            <UiDataView
                :value="filteredAndSortedShows"
                layout="list"
                data-key="id"
                paginator
                :rows="20"
            >
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm text-surface-600">
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
                        <div class="flex items-center gap-4">
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
                <template #list="{ items }">
                    <div class="flex flex-col gap-2">
                        <UiPanel
                            v-for="show in items"
                            :key="show.id"
                            :toggleable="true"
                            :collapsed="!expandedPanels[show.id]"
                            @toggle="togglePanel(show.id)"
                        >
                            <template #header>
                                <div
                                    class="flex w-full items-center justify-between gap-4 pr-3"
                                >
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{
                                            show.name
                                        }}</span>
                                        <span class="text-sm text-surface-500">
                                            {{
                                                formatDateRange(
                                                    show.start_at,
                                                    show.end_at,
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-if="getLocationString(show)"
                                            class="text-sm text-surface-500"
                                        >
                                            {{ getLocationString(show) }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div class="flex flex-col gap-4 pt-4">
                                <div class="flex flex-col gap-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span
                                                class="text-sm font-medium text-surface-700"
                                                >Start</span
                                            >
                                            <p class="text-sm text-surface-600">
                                                {{ formatDate(show.start_at) }}
                                            </p>
                                        </div>
                                        <div>
                                            <span
                                                class="text-sm font-medium text-surface-700"
                                                >End</span
                                            >
                                            <p class="text-sm text-surface-600">
                                                {{ formatDate(show.end_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        show.location_name ||
                                        show.location_address ||
                                        show.location_city ||
                                        show.location_state ||
                                        show.location_zip
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <span
                                        class="text-sm font-medium text-surface-700"
                                        >Location</span
                                    >
                                    <div class="text-sm text-surface-600">
                                        <p v-if="show.location_name">
                                            {{ show.location_name }}
                                        </p>
                                        <p v-if="show.location_address">
                                            {{ show.location_address }}
                                        </p>
                                        <p>
                                            <span v-if="show.location_city">{{
                                                show.location_city
                                            }}</span
                                            ><span
                                                v-if="
                                                    show.location_city &&
                                                    show.location_state
                                                "
                                                >, </span
                                            ><span v-if="show.location_state">{{
                                                show.location_state
                                            }}</span
                                            ><span
                                                v-if="
                                                    (show.location_city ||
                                                        show.location_state) &&
                                                    show.location_zip
                                                "
                                            >
                                            </span
                                            ><span v-if="show.location_zip">{{
                                                show.location_zip
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="show.description"
                                    class="flex flex-col gap-2"
                                >
                                    <span
                                        class="text-sm font-medium text-surface-700"
                                        >Description</span
                                    >
                                    <p class="text-sm text-surface-600">
                                        {{ show.description }}
                                    </p>
                                </div>

                                <div
                                    v-if="show.website"
                                    class="flex flex-col gap-2"
                                >
                                    <span
                                        class="text-sm font-medium text-surface-700"
                                        >Website</span
                                    >
                                    <a
                                        :href="show.website"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm text-primary-600 hover:underline"
                                    >
                                        {{ show.website }}
                                    </a>
                                </div>

                                <UiDivider />

                                <div class="flex gap-4">
                                    <UiButton
                                        label="Edit"
                                        @click="handleEdit(show)"
                                    />
                                    <UiButton
                                        label="Delete Show"
                                        severity="danger"
                                        outlined
                                        @click="handleDelete(show, $event)"
                                    />
                                </div>
                            </div>
                        </UiPanel>
                    </div>
                </template>

                <template #empty>
                    <div class="flex min-h-[60vh] items-center justify-center">
                        <p class="text-lg text-surface-500">No shows found</p>
                    </div>
                </template>
            </UiDataView>
        </div>
    </AppLayout>
</template>
