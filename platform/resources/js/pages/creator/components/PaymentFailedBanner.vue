<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const account = computed(() => page.props.account as { type?: string; subscription_status?: string } | null);

const showBanner = computed(
    () => account.value?.type === 'creator' && account.value?.subscription_status === 'past_due',
);

function openBillingPortal(): void {
    router.visit('/creator/billing/portal');
}
</script>

<template>
    <div
        v-if="showBanner"
        class="flex flex-wrap items-center justify-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950/40"
        role="alert"
    >
        <p class="text-amber-900 dark:text-amber-100">
            Your payment failed. Update your payment method to avoid losing access.
        </p>
        <UiButton
            size="small"
            :outlined="true"
            class="border-amber-700 text-amber-900 dark:border-amber-500 dark:text-amber-100"
            @click="openBillingPortal"
        >
            Update Payment Method
        </UiButton>
    </div>
</template>
