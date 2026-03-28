<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const shopify = computed(
    () =>
        page.props.shopify as {
            has_sync_errors?: boolean;
            integration_id?: number | null;
        } | null,
);

const showBanner = computed(() => shopify.value?.has_sync_errors === true);

function dismiss(): void {
    if (!shopify.value?.integration_id) return;
    router.post(
        `/creator/shopify/${shopify.value.integration_id}/errors/dismiss`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <div
        v-if="showBanner"
        class="flex flex-wrap items-center justify-between gap-3 border-b border-red-200 bg-red-50 px-4 py-3"
        role="alert"
    >
        <p class="text-sm text-red-800">
            <strong>Shopify sync error</strong> — One or more updates failed to
            sync to your Shopify store. Please contact
            <a
                href="mailto:support@fibermade.app"
                class="underline hover:no-underline"
                >support@fibermade.app</a
            >
            for help.
        </p>
        <button
            type="button"
            class="text-red-500 hover:text-red-700"
            aria-label="Dismiss"
            @click="dismiss"
        >
            ×
        </button>
    </div>
</template>
