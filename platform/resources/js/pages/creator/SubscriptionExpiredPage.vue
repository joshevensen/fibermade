<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { Head, router } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Subscription expired" />
    <CreatorLayout page-title="Subscription expired">
        <div
            class="flex flex-col gap-6 rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-950/30"
        >
            <h2 class="text-lg font-semibold">Your subscription has ended</h2>
            <p class="text-muted-foreground">
                Creator access is no longer active. Reactivate your subscription
                to manage your catalog, orders, and stores again.
            </p>
            <div>
                <UiButton
                    data-test="reactivate-subscription-button"
                    @click="
                        () => router.visit('/creator/subscription/reactivate')
                    "
                >
                    Reactivate subscription
                </UiButton>
            </div>
        </div>
    </CreatorLayout>
</template>
