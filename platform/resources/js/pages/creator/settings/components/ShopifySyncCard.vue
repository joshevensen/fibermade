<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiToggleSwitch from '@/components/ui/UiToggleSwitch.vue';
import { useToast } from '@/composables/useToast';
import { computed, onUnmounted, ref, watch } from 'vue';

interface SyncStepResult {
    created: number;
    updated: number;
    failed: number;
}

interface SyncError {
    step: string;
    message: string;
    [key: string]: unknown;
}

interface SyncState {
    status: 'idle' | 'running' | 'complete' | 'failed';
    current_step?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    last_result?: {
        products?: SyncStepResult;
        collections?: SyncStepResult;
        inventory?: SyncStepResult;
    };
    errors?: SyncError[];
}

interface RecentError {
    id: number;
    message: string;
    created_at: string | null;
}

interface Props {
    shopify?: {
        connected: boolean;
        auto_sync: boolean;
        sync: SyncState;
        recent_errors: RecentError[];
    } | null;
}

const props = defineProps<Props>();

const { showError, showSuccess } = useToast();

const syncState = ref<SyncState>(props.shopify?.sync ?? { status: 'idle' });
const autoSync = ref<boolean>(props.shopify?.auto_sync ?? false);
const savingAutoSync = ref(false);
const triggering = ref(false);
const errorsExpanded = ref(false);

let pollInterval: ReturnType<typeof setInterval> | null = null;

const isRunning = computed(() => syncState.value.status === 'running');

const currentStepLabel = computed(() => {
    const step = syncState.value.current_step;
    if (!step) return 'Syncing...';
    const labels: Record<string, string> = {
        products: 'Syncing products...',
        collections: 'Syncing collections...',
        inventory: 'Syncing inventory...',
    };
    return labels[step] ?? 'Syncing...';
});

const completedAt = computed(() => {
    const d = syncState.value.completed_at;
    if (!d) return null;
    try {
        return new Date(d).toLocaleString(undefined, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    } catch {
        return null;
    }
});

const lastResult = computed(() => syncState.value.last_result ?? null);

const syncErrors = computed<SyncError[]>(() => syncState.value.errors ?? []);

const errorCount = computed(() => {
    const result = lastResult.value;
    if (!result) return 0;
    return (
        (result.products?.failed ?? 0) +
        (result.collections?.failed ?? 0) +
        (result.inventory?.failed ?? 0)
    );
});

function getCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function startPolling(): void {
    stopPolling();
    pollInterval = setInterval(async () => {
        await pollStatus();
    }, 3000);
}

function stopPolling(): void {
    if (pollInterval !== null) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

async function pollStatus(): Promise<void> {
    try {
        const response = await fetch('/creator/shopify/sync/status', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            connected: boolean;
            sync: SyncState;
        };

        syncState.value = data.sync ?? { status: 'idle' };

        if (syncState.value.status !== 'running') {
            stopPolling();
        }
    } catch {
        // Silently fail — polling will retry
    }
}

watch(
    () => syncState.value.status,
    (status) => {
        if (status === 'running') {
            startPolling();
        } else {
            stopPolling();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    stopPolling();
});

async function triggerSync(endpoint: string, label: string): Promise<void> {
    if (isRunning.value || triggering.value) return;

    triggering.value = true;

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        if (response.status === 409) {
            showError('A sync is already running.');
            return;
        }

        if (!response.ok) {
            const payload = (await response.json().catch(() => null)) as {
                message?: string;
            } | null;
            throw new Error(
                payload?.message ?? `Could not start ${label} sync.`,
            );
        }

        const data = (await response.json()) as { sync: SyncState };
        syncState.value = data.sync ?? { status: 'running' };
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : `Could not start ${label} sync.`;
        showError(message);
    } finally {
        triggering.value = false;
    }
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
        // Revert toggle on failure
        autoSync.value = !value;
    } finally {
        savingAutoSync.value = false;
    }
}

function syncAll(): void {
    void triggerSync('/creator/shopify/sync/all', 'full');
}

function syncProducts(): void {
    void triggerSync('/creator/shopify/sync/products', 'products');
}

function syncCollections(): void {
    void triggerSync('/creator/shopify/sync/collections', 'collections');
}

function syncInventory(): void {
    void triggerSync('/creator/shopify/sync/inventory', 'inventory');
}

function formatStepCount(result: SyncStepResult): string {
    const total = result.created + result.updated;
    return `${total} synced, ${result.failed} failed`;
}
</script>

<template>
    <UiCard>
        <template #title>Shopify Sync</template>
        <template #subtitle>
            Pull your products, collections, and inventory from Shopify
        </template>
        <template #content>
            <div class="flex flex-col gap-5">
                <!-- Not connected notice -->
                <div
                    v-if="!shopify?.connected"
                    class="rounded-lg border border-surface-200 bg-surface-50 p-4 text-sm text-surface-500"
                >
                    Connect your Shopify store to enable sync controls.
                </div>

                <template v-else>
                    <!-- Auto-sync toggle -->
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-medium text-surface-900 dark:text-surface-100"
                            >
                                Auto-sync
                            </p>
                            <p class="text-muted-foreground text-xs">
                                Automatically sync when products change in
                                Shopify
                            </p>
                        </div>
                        <UiToggleSwitch
                            :model-value="autoSync"
                            :disabled="savingAutoSync"
                            @update:model-value="saveAutoSync"
                        />
                    </div>

                    <div
                        class="border-t border-surface-200 dark:border-surface-700"
                    />

                    <!-- Running state -->
                    <div v-if="isRunning" class="flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <svg
                                class="h-4 w-4 shrink-0 animate-spin text-primary-500"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            <span
                                class="text-sm text-surface-700 dark:text-surface-300"
                            >
                                {{ currentStepLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Sync buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <UiButton
                            severity="secondary"
                            :disabled="isRunning || triggering"
                            @click="syncProducts"
                        >
                            Sync Products
                        </UiButton>
                        <UiButton
                            severity="secondary"
                            :disabled="isRunning || triggering"
                            @click="syncCollections"
                        >
                            Sync Collections
                        </UiButton>
                        <UiButton
                            severity="secondary"
                            :disabled="isRunning || triggering"
                            @click="syncInventory"
                        >
                            Sync Inventory
                        </UiButton>
                        <UiButton
                            :disabled="isRunning || triggering"
                            :loading="triggering && !isRunning"
                            @click="syncAll"
                        >
                            Sync All
                        </UiButton>
                    </div>

                    <!-- Last sync summary -->
                    <div
                        v-if="
                            syncState.status === 'complete' ||
                            syncState.status === 'failed'
                        "
                        class="flex flex-col gap-2 rounded-lg border border-surface-200 bg-surface-50 p-3 dark:border-surface-700 dark:bg-surface-800"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="text-xs font-medium text-surface-700 dark:text-surface-300"
                            >
                                Last sync
                            </p>
                            <span
                                v-if="syncState.status === 'failed'"
                                class="text-xs font-medium text-red-600 dark:text-red-400"
                            >
                                Failed
                            </span>
                        </div>

                        <p
                            v-if="completedAt"
                            class="text-muted-foreground text-xs"
                        >
                            {{ completedAt }}
                        </p>

                        <div v-if="lastResult" class="flex flex-col gap-1">
                            <div
                                v-if="lastResult.products"
                                class="flex justify-between text-xs"
                            >
                                <span
                                    class="text-surface-600 dark:text-surface-400"
                                    >Products</span
                                >
                                <span
                                    class="text-surface-800 dark:text-surface-200"
                                >
                                    {{ formatStepCount(lastResult.products) }}
                                </span>
                            </div>
                            <div
                                v-if="lastResult.collections"
                                class="flex justify-between text-xs"
                            >
                                <span
                                    class="text-surface-600 dark:text-surface-400"
                                    >Collections</span
                                >
                                <span
                                    class="text-surface-800 dark:text-surface-200"
                                >
                                    {{
                                        formatStepCount(lastResult.collections)
                                    }}
                                </span>
                            </div>
                            <div
                                v-if="lastResult.inventory"
                                class="flex justify-between text-xs"
                            >
                                <span
                                    class="text-surface-600 dark:text-surface-400"
                                    >Inventory</span
                                >
                                <span
                                    class="text-surface-800 dark:text-surface-200"
                                >
                                    {{ formatStepCount(lastResult.inventory) }}
                                </span>
                            </div>
                        </div>

                        <!-- Error count + expand -->
                        <div v-if="errorCount > 0">
                            <button
                                type="button"
                                class="flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                @click="errorsExpanded = !errorsExpanded"
                            >
                                {{ errorCount }}
                                {{ errorCount === 1 ? 'error' : 'errors' }}
                                <svg
                                    class="h-3 w-3 transition-transform"
                                    :class="{ 'rotate-180': errorsExpanded }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <ul
                                v-if="errorsExpanded && syncErrors.length"
                                class="mt-2 flex flex-col gap-1"
                            >
                                <li
                                    v-for="(err, i) in syncErrors"
                                    :key="i"
                                    class="rounded bg-red-50 px-2 py-1 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300"
                                >
                                    <span class="mr-1 font-medium capitalize"
                                        >{{ err.step }}:</span
                                    >
                                    {{ err.message }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent integration log errors -->
                    <div
                        v-if="
                            shopify.recent_errors &&
                            shopify.recent_errors.length > 0
                        "
                        class="flex flex-col gap-2"
                    >
                        <p
                            class="text-xs font-medium text-surface-700 dark:text-surface-300"
                        >
                            Recent errors
                        </p>
                        <ul class="flex flex-col gap-1">
                            <li
                                v-for="err in shopify.recent_errors"
                                :key="err.id"
                                class="rounded bg-red-50 px-2 py-1 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300"
                            >
                                {{ err.message }}
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </template>
    </UiCard>
</template>
