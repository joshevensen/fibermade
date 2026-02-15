<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const account = computed(
    () => page.props.account as { type?: string; subscription_status?: string } | null,
);
const nextBillingDate = computed(() => page.props.next_billing_date as string | null | undefined);

const isCreator = computed(() => account.value?.type === 'creator');

const formattedNextBilling = computed(() => {
    const d = nextBillingDate.value;
    if (!d) return null;
    try {
        return new Date(d + 'T00:00:00').toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return null;
    }
});

const statusLabel = computed(() => {
    const s = account.value?.subscription_status;
    if (!s) return '—';
    const labels: Record<string, string> = {
        active: 'Active',
        past_due: 'Past due',
        cancelled: 'Cancelled',
        inactive: 'Inactive',
        refunded: 'Refunded',
    };
    return labels[s] ?? s;
});

function openBillingPortal(): void {
    router.visit('/creator/billing/portal');
}
</script>

<template>
    <UiCard v-if="isCreator" class="rounded-xl">
        <template #title>Billing</template>
        <template #subtitle>
            <span class="capitalize">{{ statusLabel }}</span>
            <span v-if="account?.subscription_status === 'active'" class="text-muted-foreground">
                — $39/month
                <template v-if="formattedNextBilling">
                    · Next billing {{ formattedNextBilling }}
                </template>
            </span>
        </template>
        <template #content>
            <div class="flex flex-col gap-3">
                <p class="text-muted-foreground text-sm">
                    Manage your subscription, payment method, and invoices in the billing portal.
                </p>
                <UiButton @click="openBillingPortal">Manage Billing</UiButton>
            </div>
        </template>
    </UiCard>
</template>
