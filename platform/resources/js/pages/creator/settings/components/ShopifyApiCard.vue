<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { useToast } from '@/composables/useToast';
import { computed, ref } from 'vue';

const { showError, showSuccess } = useToast();

const token = ref('');
const loading = ref(false);
const copied = ref(false);
const errorMessage = ref('');

const hasToken = computed(() => token.value.length > 0);

function getCsrfToken(): string {
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    return csrf ?? '';
}

async function generateToken(): Promise<void> {
    loading.value = true;
    errorMessage.value = '';
    copied.value = false;

    try {
        const response = await fetch('/creator/settings/api-token', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                _token: getCsrfToken(),
            }),
        });

        if (!response.ok) {
            if (response.status === 419) {
                throw new Error(
                    'Your session expired. Refresh the page and try again.',
                );
            }

            const payload = (await response.json().catch(() => null)) as {
                message?: string;
            } | null;
            throw new Error(
                payload?.message ??
                    'Could not generate token. Please try again.',
            );
        }

        const payload = (await response.json()) as { token?: string };
        if (!payload.token) {
            throw new Error('Token response was invalid. Please try again.');
        }

        token.value = payload.token;
        showSuccess('Shopify API token generated.');
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : 'Could not generate token. Please try again.';
        errorMessage.value = message;
        showError(message);
    } finally {
        loading.value = false;
    }
}

async function copyToken(): Promise<void> {
    if (!token.value) {
        return;
    }

    try {
        if (window.isSecureContext && navigator.clipboard) {
            await navigator.clipboard.writeText(token.value);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = token.value;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            textArea.style.pointerEvents = 'none';
            document.body.appendChild(textArea);
            textArea.select();
            textArea.setSelectionRange(0, textArea.value.length);
            const copied = document.execCommand('copy');
            document.body.removeChild(textArea);

            if (!copied) {
                throw new Error('Copy command failed.');
            }
        }

        copied.value = true;
        showSuccess('Token copied to clipboard.');
    } catch {
        showError(
            'Clipboard copy failed. Please copy the token manually from the field.',
        );
    }
}
</script>

<template>
    <UiCard>
        <template #title>Shopify API</template>
        <template #subtitle>Connect your Shopify store to Fibermade</template>
        <template #content>
            <div class="flex flex-col gap-3">
                <p class="text-muted-foreground text-sm">
                    Generate a Fibermade API token and paste it into the Shopify
                    app Connect screen to link your store.
                </p>

                <UiButton :loading="loading" @click="generateToken">
                    Generate token
                </UiButton>

                <div v-if="hasToken" class="flex flex-col gap-2">
                    <label
                        for="shopify-api-token"
                        class="text-sm font-medium text-surface-700"
                    >
                        Shopify API token
                    </label>
                    <div class="flex flex-col gap-2 lg:flex-row">
                        <input
                            id="shopify-api-token"
                            :value="token"
                            readonly
                            spellcheck="false"
                            class="w-full rounded-md border border-surface-300 bg-surface-0 px-3 py-2 font-mono text-xs text-surface-800"
                        />
                        <UiButton @click="copyToken">Copy token</UiButton>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        This token is shown only once. Store it securely and use
                        it in the Shopify app.
                    </p>
                    <p v-if="copied" class="text-xs text-green-600">
                        Copied to clipboard.
                    </p>
                </div>

                <p v-if="errorMessage" class="text-sm text-red-600">
                    {{ errorMessage }}
                </p>
            </div>
        </template>
    </UiCard>
</template>
