<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';

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
}

interface Props {
    upcomingShows: Show[];
}

const props = defineProps<Props>();

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatLocation(show: Show): string {
    const parts: string[] = [];

    if (show.location_name) {
        parts.push(show.location_name);
    }

    if (show.city) {
        parts.push(show.city);
    }

    if (show.state_region) {
        parts.push(show.state_region);
    }

    return parts.join(', ') || 'Location TBD';
}
</script>

<template>
    <UiCard>
        <template #title> Upcoming Shows </template>
        <template #content>
            <div class="space-y-4">
                <div v-if="upcomingShows.length === 0" class="text-surface-500">
                    No upcoming shows in the next 90 days
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="show in upcomingShows"
                        :key="show.id"
                        class="border-b border-surface-200 pb-3 last:border-0 last:pb-0"
                    >
                        <div class="font-medium text-surface-900">
                            {{ show.name }}
                        </div>
                        <div class="mt-1 text-sm text-surface-600">
                            {{ formatDate(show.start_at) }}
                            <span
                                v-if="
                                    show.end_at && show.end_at !== show.start_at
                                "
                            >
                                - {{ formatDate(show.end_at) }}
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-surface-500">
                            {{ formatLocation(show) }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </UiCard>
</template>
