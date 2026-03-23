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

interface PushSyncState {
    status: 'idle' | 'running' | 'complete' | 'failed';
    current_step?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    last_result?: {
        colorways?: SyncStepResult;
        collections?: SyncStepResult;
    };
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
        push_sync?: PushSyncState;
        recent_errors: RecentError[];
    } | null;
}

const props = defineProps<Props>();

const { showError, showSuccess } = useToast();

const syncState = ref<SyncState>(props.shopify?.sync ?? { status: 'idle' });
const pushSyncState = ref<PushSyncState>(
    props.shopify?.push_sync ?? { status: 'idle' },
);
const autoSync = ref<boolean>(props.shopify?.auto_sync ?? false);
const savingAutoSync = ref(false);
const triggeringPull = ref(false);
const triggeringPush = ref(false);
const pushErrorsExpanded = ref(false);

let pullPollInterval: ReturnType<typeof setInterval> | null = null;
let pushPollInterval: ReturnType<typeof setInterval> | null = null;

const isPullRunning = computed(() => syncState.value.status === 'running');
const isPushRunning = computed(() => pushSyncState.value.status === 'running');

const pushCurrentStepLabel = computed(() => {
    const step = pushSyncState.value.current_step;
    if (!step) return 'Pushing...';
    const labels: Record<string, string> = {
        colorways: 'Pushing colorways...',
        collections: 'Pushing collections...',
    };
    return labels[step] ?? 'Pushing...';
});

const pushCompletedAt = computed(() => {
    const d = pushSyncState.value.completed_at;
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

const pushLastResult = computed(() => pushSyncState.value.last_result ?? null);

const pushErrorCount = computed(() => {
    const result = pushLastResult.value;
    if (!result) return 0;
    return (result.colorways?.failed ?? 0) + (result.collections?.failed ?? 0);
});

function getCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function startPullPolling(): void {
    stopPullPolling();
    pullPollInterval = setInterval(async () => {
        await pollStatus();
    }, 3000);
}

function stopPullPolling(): void {
    if (pullPollInterval !== null) {
        clearInterval(pullPollInterval);
        pullPollInterval = null;
    }
}

function startPushPolling(): void {
    stopPushPolling();
    pushPollInterval = setInterval(async () => {
        await pollStatus();
    }, 3000);
}

function stopPushPolling(): void {
    if (pushPollInterval !== null) {
        clearInterval(pushPollInterval);
        pushPollInterval = null;
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
            push_sync: PushSyncState;
        };

        syncState.value = data.sync ?? { status: 'idle' };
        pushSyncState.value = data.push_sync ?? { status: 'idle' };

        if (syncState.value.status !== 'running') {
            stopPullPolling();
        }
        if (pushSyncState.value.status !== 'running') {
            stopPushPolling();
        }
    } catch {
        // Silently fail — polling will retry
    }
}

watch(
    () => syncState.value.status,
    (status) => {
        if (status === 'running') {
            startPullPolling();
        } else {
            stopPullPolling();
        }
    },
    { immediate: true },
);

watch(
    () => pushSyncState.value.status,
    (status) => {
        if (status === 'running') {
            startPushPolling();
        } else {
            stopPushPolling();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    stopPullPolling();
    stopPushPolling();
});

async function triggerSync(endpoint: string, label: string): Promise<void> {
    if (isPullRunning.value || triggeringPull.value) return;

    triggeringPull.value = true;

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
        triggeringPull.value = false;
    }
}

async function triggerPushAll(): Promise<void> {
    if (isPushRunning.value || triggeringPush.value) return;

    triggeringPush.value = true;

    try {
        const response = await fetch('/creator/shopify/push/all', {
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
            showError('A push is already running.');
            return;
        }

        if (!response.ok) {
            const payload = (await response.json().catch(() => null)) as {
                message?: string;
            } | null;
            throw new Error(
                payload?.message ?? 'Could not start push to Shopify.',
            );
        }

        const data = (await response.json()) as { push_sync: PushSyncState };
        pushSyncState.value = data.push_sync ?? { status: 'running' };
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : 'Could not start push to Shopify.';
        showError(message);
    } finally {
        triggeringPush.value = false;
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
            Push your catalog to Shopify and reconcile inventory
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
                                Automatically push changes to Shopify when
                                catalog items are updated
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

                    <!-- Push to Shopify section -->
                    <div class="flex flex-col gap-3">
                        <div>
                            <p
                                class="text-sm font-medium text-surface-900 dark:text-surface-100"
                            >
                                Push all to Shopify
                            </p>
                            <p class="mt-0.5 text-xs text-surface-500">
                                This will overwrite all product data in your
                                Shopify store with current Fibermade values.
                            </p>
                        </div>

                        <!-- Push running state -->
                        <div v-if="isPushRunning" class="flex items-center gap-3">
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
                                {{ pushCurrentStepLabel }}
                            </span>
                        </div>

                        <UiButton
                            :disabled="isPushRunning || triggeringPush"
                            :loading="triggeringPush && !isPushRunning"
                            @click="triggerPushAll"
                        >
                            Push All to Shopify
                        </UiButton>

                        <!-- Push last result -->
                        <div
                            v-if="
                                pushSyncState.status === 'complete' ||
                                pushSyncState.status === 'failed'
                            "
                            class="flex flex-col gap-2 rounded-lg border border-surface-200 bg-surface-50 p-3 dark:border-surface-700 dark:bg-surface-800"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p
                                    class="text-xs font-medium text-surface-700 dark:text-surface-300"
                                >
                                    Last push
                                </p>
                                <span
                                    v-if="pushSyncState.status === 'failed'"
                                    class="text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    Failed
                                </span>
                            </div>

                            <p
                                v-if="pushCompletedAt"
                                class="text-muted-foreground text-xs"
                            >
                                {{ pushCompletedAt }}
                            </p>

                            <div
                                v-if="pushLastResult"
                                class="flex flex-col gap-1"
                            >
                                <div
                                    v-if="pushLastResult.colorways"
                                    class="flex justify-between text-xs"
                                >
                                    <span
                                        class="text-surface-600 dark:text-surface-400"
                                        >Colorways</span
                                    >
                                    <span
                                        class="text-surface-800 dark:text-surface-200"
                                    >
                                        {{
                                            formatStepCount(
                                                pushLastResult.colorways,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="pushLastResult.collections"
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
                                            formatStepCount(
                                                pushLastResult.collections,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Error count + expand -->
                            <div v-if="pushErrorCount > 0">
                                <button
                                    type="button"
                                    class="flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                    @click="pushErrorsExpanded = !pushErrorsExpanded"
                                >
                                    {{ pushErrorCount }}
                                    {{
                                        pushErrorCount === 1
                                            ? 'error'
                                            : 'errors'
                                    }}
                                    <svg
                                        class="h-3 w-3 transition-transform"
                                        :class="{
                                            'rotate-180': pushErrorsExpanded,
                                        }"
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
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-t border-surface-200 dark:border-surface-700"
                    />

                    <!-- Reconcile inventory from Shopify -->
                    <div class="flex flex-col gap-3">
                        <div>
                            <p
                                class="text-sm font-medium text-surface-900 dark:text-surface-100"
                            >
                                Reconcile inventory
                            </p>
                            <p class="mt-0.5 text-xs text-surface-500">
                                Import inventory quantities from Shopify to
                                account for customer purchases.
                            </p>
                        </div>

                        <!-- Inventory sync running state -->
                        <div
                            v-if="isPullRunning"
                            class="flex items-center gap-3"
                        >
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
                                Syncing inventory...
                            </span>
                        </div>

                        <UiButton
                            severity="secondary"
                            :disabled="isPullRunning || triggeringPull"
                            :loading="triggeringPull && !isPullRunning"
                            @click="syncInventory"
                        >
                            Reconcile Inventory from Shopify
                        </UiButton>
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
