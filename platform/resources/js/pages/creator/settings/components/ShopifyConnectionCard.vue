<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    shopify?: {
        connected: boolean;
        shop: string | null;
        connected_since: string | null;
        connect_token: string | null;
    } | null;
    connectToken?: string | null;
}

const props = defineProps<Props>();

const { showError, showSuccess } = useToast();

const displayToken = ref<string | null>(
    props.connectToken ?? props.shopify?.connect_token ?? null,
);
const confirmingReset = ref(false);
const resetting = ref(false);
const refreshing = ref(false);

function refreshStatus(): void {
    refreshing.value = true;
    router.reload({
        only: ['shopify'],
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

const connectedSinceFormatted = computed(() => {
    const d = props.shopify?.connected_since;
    if (!d) return null;
    try {
        return new Date(d + 'T00:00:00').toLocaleDateString(undefined, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return null;
    }
});

function getCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function copyToken(): Promise<void> {
    if (!displayToken.value) {
        return;
    }

    try {
        if (window.isSecureContext && navigator.clipboard) {
            await navigator.clipboard.writeText(displayToken.value);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = displayToken.value;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            textArea.style.pointerEvents = 'none';
            document.body.appendChild(textArea);
            textArea.select();
            textArea.setSelectionRange(0, textArea.value.length);
            const didCopy = document.execCommand('copy');
            document.body.removeChild(textArea);

            if (!didCopy) {
                throw new Error('Copy command failed.');
            }
        }

        showSuccess('Token copied to clipboard.');
    } catch {
        showError(
            'Clipboard copy failed. Please copy the token manually from the field.',
        );
    }
}

async function resetToken(): Promise<void> {
    resetting.value = true;

    try {
        const response = await fetch(
            '/creator/settings/shopify-connect-token/reset',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            },
        );

        if (!response.ok) {
            throw new Error('Could not reset token. Please try again.');
        }

        const payload = (await response.json()) as { connect_token?: string };
        if (!payload.connect_token) {
            throw new Error('Reset response was invalid. Please try again.');
        }

        displayToken.value = payload.connect_token;
        confirmingReset.value = false;
        showSuccess('Connect token reset. Your Shopify connection has been deactivated.');
        router.reload({ only: ['shopify'] });
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : 'Could not reset token. Please try again.';
        showError(message);
    } finally {
        resetting.value = false;
    }
}
</script>

<template>
    <UiCard>
        <template #title>Shopify Connection</template>
        <template #subtitle>Connect your Shopify store to Fibermade</template>
        <template #content>
            <div class="flex flex-col gap-4">
                <!-- Connection status -->
                <div
                    class="flex items-center gap-3 rounded-lg border border-surface-200 bg-surface-50 p-3"
                >
                    <span
                        class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="
                            shopify?.connected
                                ? 'bg-green-500'
                                : 'bg-surface-400'
                        "
                    />
                    <div class="min-w-0 flex-1">
                        <template v-if="shopify?.connected">
                            <p
                                class="truncate text-sm font-medium text-surface-900"
                            >
                                {{ shopify.shop }}
                            </p>
                            <p
                                v-if="connectedSinceFormatted"
                                class="text-muted-foreground text-xs"
                            >
                                Connected since {{ connectedSinceFormatted }}
                            </p>
                        </template>
                        <template v-else>
                            <p class="text-sm text-surface-500">
                                Not connected
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Connect token display -->
                <div class="flex flex-col gap-3">
                    <p class="text-sm font-medium text-surface-700">
                        Your Fibermade Connect Token
                    </p>

                    <div class="flex gap-2">
                        <input
                            :value="displayToken ?? ''"
                            readonly
                            spellcheck="false"
                            class="min-w-0 flex-1 rounded-md border border-surface-300 bg-surface-50 px-3 py-2 font-mono text-xs text-surface-800"
                        />
                        <UiButton class="shrink-0" @click="copyToken">
                            Copy
                        </UiButton>
                    </div>

                    <p class="text-muted-foreground text-xs">
                        Paste this into the Shopify app to connect your store.
                    </p>

                    <!-- Reset token + Refresh status -->
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div v-if="!confirmingReset">
                                <button
                                    type="button"
                                    class="text-xs text-red-600 underline hover:text-red-700"
                                    @click="confirmingReset = true"
                                >
                                    Reset token
                                </button>
                            </div>

                            <div v-else class="flex flex-col gap-2">
                                <p class="text-xs text-surface-600">
                                    This will disconnect any stores currently
                                    linked with this token. Are you sure?
                                </p>
                                <div class="flex gap-2">
                                    <UiButton
                                        severity="danger"
                                        size="small"
                                        :loading="resetting"
                                        @click="resetToken"
                                    >
                                        Yes, reset
                                    </UiButton>
                                    <UiButton
                                        severity="secondary"
                                        size="small"
                                        @click="confirmingReset = false"
                                    >
                                        Cancel
                                    </UiButton>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 text-xs text-primary-600 hover:text-primary-700 disabled:opacity-50"
                            :disabled="refreshing"
                            @click="refreshStatus"
                        >
                            {{
                                refreshing ? 'Refreshing...' : 'Refresh status'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </UiCard>
</template>
