<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';
import UiToggleSwitch from '@/components/ui/UiToggleSwitch.vue';
import { useToast } from '@/composables/useToast';
import { ref } from 'vue';

interface Props {
    shopify?: {
        connected: boolean;
        auto_sync: boolean;
    } | null;
}

const props = defineProps<Props>();

const { showError, showSuccess } = useToast();

const autoSync = ref<boolean>(props.shopify?.auto_sync ?? false);
const savingAutoSync = ref(false);

function getCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function saveAutoSync(value: boolean): Promise<void> {
    savingAutoSync.value = true;
    try {
        const response = await fetch('/creator/shopify/settings', {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ auto_sync: value }),
        });

        if (!response.ok) {
            throw new Error('Could not save auto-sync setting.');
        }

        autoSync.value = value;
        showSuccess(value ? 'Auto-sync enabled.' : 'Auto-sync disabled.');
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : 'Could not save auto-sync setting.';
        showError(message);
        autoSync.value = !value;
    } finally {
        savingAutoSync.value = false;
    }
}
</script>

<template>
    <UiCard>
        <template #title>Shopify Settings</template>
        <template #content>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-surface-900">
                        Auto-sync
                    </p>
                    <p class="text-muted-foreground text-xs">
                        Automatically push changes to Shopify when catalog items
                        are updated
                    </p>
                </div>
                <UiToggleSwitch
                    :model-value="autoSync"
                    :disabled="!shopify?.connected || savingAutoSync"
                    @update:model-value="saveAutoSync"
                />
            </div>
        </template>
    </UiCard>
</template>
