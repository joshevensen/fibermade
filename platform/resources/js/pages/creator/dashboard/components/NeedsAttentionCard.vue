<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';
import { Link } from '@inertiajs/vue3';
import { IconCircleCheck } from '@tabler/icons-vue';
import { computed } from 'vue';

interface NeedsAttention {
    pending_orders: number;
    pending_store_invites: number;
}

interface Props {
    needsAttention: NeedsAttention;
}

const props = defineProps<Props>();

const hasPendingOrders = computed(
    () => (props.needsAttention.pending_orders ?? 0) > 0,
);
const hasPendingInvites = computed(
    () => (props.needsAttention.pending_store_invites ?? 0) > 0,
);
const hasAny = computed(
    () => hasPendingOrders.value || hasPendingInvites.value,
);
</script>

<template>
    <UiCard>
        <template #title> Needs Attention </template>
        <template #content>
            <div v-if="!hasAny" class="flex flex-col items-center gap-2 py-6">
                <IconCircleCheck
                    class="size-10 text-green-600 dark:text-green-400"
                    aria-hidden
                />
                <p class="text-center font-medium text-surface-700">
                    Nothing needs attention
                </p>
            </div>
            <div v-else class="space-y-3">
                <Link
                    v-if="hasPendingOrders"
                    href="/creator/orders?status=open"
                    class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 transition-colors hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:hover:bg-amber-900/30"
                >
                    <span class="font-medium text-surface-900">
                        Pending orders
                    </span>
                    <span
                        class="shrink-0 rounded-full bg-amber-200 px-2 py-0.5 text-sm font-semibold text-amber-900 dark:bg-amber-700 dark:text-amber-100"
                    >
                        {{ needsAttention.pending_orders }}
                    </span>
                </Link>
                <Link
                    v-if="hasPendingInvites"
                    href="/creator/stores?status=invited"
                    class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 transition-colors hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:hover:bg-amber-900/30"
                >
                    <span class="font-medium text-surface-900">
                        Pending store invites
                    </span>
                    <span
                        class="shrink-0 rounded-full bg-amber-200 px-2 py-0.5 text-sm font-semibold text-amber-900 dark:bg-amber-700 dark:text-amber-100"
                    >
                        {{ needsAttention.pending_store_invites }}
                    </span>
                </Link>
            </div>
        </template>
    </UiCard>
</template>
