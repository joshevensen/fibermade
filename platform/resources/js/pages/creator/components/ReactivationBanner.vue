<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const account = computed(
    () =>
        page.props.account as {
            type?: string;
            subscription_status?: string;
            reactivation_days_remaining?: number | null;
        } | null,
);

const showBanner = computed(
    () =>
        account.value?.type === 'creator' &&
        account.value?.subscription_status === 'inactive',
);

const daysRemaining = computed(
    () => account.value?.reactivation_days_remaining ?? null,
);

function goToReactivate(): void {
    router.visit('/creator/subscription/reactivate');
}
</script>

<template>
    <div
        v-if="showBanner"
        class="flex flex-wrap items-center justify-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950/40"
        role="alert"
    >
        <p class="text-amber-900 dark:text-amber-100">
            Your subscription has ended.
            <template v-if="daysRemaining !== null">
                {{ daysRemaining }}
                {{ daysRemaining === 1 ? 'day' : 'days' }} left to reactivate
                and keep your data.
            </template>
            <template v-else>
                Reactivate within 90 days to keep your data.
            </template>
        </p>
        <UiButton size="small" @click="goToReactivate">
            Reactivate subscription
        </UiButton>
    </div>
</template>
