<script setup lang="ts">
import UiAddress from '@/components/ui/UiAddress.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiIcon from '@/components/ui/UiIcon.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';

interface Creator {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    state_region?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
}

interface Props {
    creator: Creator;
    backUrl?: string | null;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
</script>

<template>
    <UiCard class="mb-4">
        <template #content>
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <UiLink
                        v-if="props.backUrl"
                        :href="props.backUrl"
                        as="button"
                        type="button"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-surface-600 no-underline transition-colors hover:bg-surface-100 hover:text-surface-900 focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        aria-label="Go back"
                    >
                        <UiIcon :name="IconList.Left" :size="24" />
                    </UiLink>
                    <div class="flex min-w-0 flex-col gap-1">
                        <h3 class="text-xl font-semibold text-surface-900">
                            {{ props.creator.name }}
                        </h3>
                        <div
                            class="flex flex-wrap gap-2 text-sm text-surface-600 lg:gap-4"
                        >
                            <p v-if="props.creator.email">
                                {{ props.creator.email }}
                            </p>
                            <p v-if="props.creator.phone">
                                {{ props.creator.phone }}
                            </p>
                            <UiAddress
                                :entity="props.creator"
                                variant="oneLine"
                            />
                        </div>
                    </div>
                </div>
                <div v-if="$slots.default" class="shrink-0">
                    <slot />
                </div>
            </div>
        </template>
    </UiCard>
</template>
