<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { useToast } from '@/composables/useToast';
import { computed, onUnmounted, ref, watch, type Ref } from 'vue';

interface SyncStepResult {
    created: number;
    updated: number;
    failed: number;
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
        sync: SyncState;
        push_sync?: PushSyncState;
        recent_errors: RecentError[];
    } | null;
    shopifyAppUrl?: string | null;
}

const props = defineProps<Props>();

const { showError } = useToast();

const syncState = ref<SyncState>(props.shopify?.sync ?? { status: 'idle' });
const pushSyncState = ref<PushSyncState>(
    props.shopify?.push_sync ?? { status: 'idle' },
);
const triggeringPullAll = ref(false);
const triggeringColorways = ref(false);
const triggeringCollections = ref(false);
const triggeringInventory = ref(false);
const triggeringPush = ref(false);

let pullPollInterval: ReturnType<typeof setInterval> | null = null;
let pushPollInterval: ReturnType<typeof setInterval> | null = null;

const isPullRunning = computed(() => syncState.value.status === 'running');
const isPushRunning = computed(() => pushSyncState.value.status === 'running');
const isAnyPullTriggering = computed(
    () =>
        triggeringPullAll.value ||
        triggeringColorways.value ||
        triggeringCollections.value ||
        triggeringInventory.value,
);

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

async function triggerPull(
    endpoint: string,
    triggeringRef: Ref<boolean>,
): Promise<void> {
    if (isPullRunning.value || isAnyPullTriggering.value) return;

    triggeringRef.value = true;

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
            throw new Error(payload?.message ?? 'Could not start sync.');
        }

        const data = (await response.json()) as { sync: SyncState };
        syncState.value = data.sync ?? { status: 'running' };
    } catch (error) {
        const message =
            error instanceof Error ? error.message : 'Could not start sync.';
        showError(message);
    } finally {
        triggeringRef.value = false;
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

function pullAll(): void {
    void triggerPull('/creator/shopify/sync/all', triggeringPullAll);
}

function pullColorways(): void {
    void triggerPull('/creator/shopify/sync/products', triggeringColorways);
}

function pullCollections(): void {
    void triggerPull(
        '/creator/shopify/sync/collections',
        triggeringCollections,
    );
}

function pullInventory(): void {
    void triggerPull('/creator/shopify/sync/inventory', triggeringInventory);
}

function formatStepCount(result: SyncStepResult): string {
    const total = result.created + result.updated;
    return `${total} synced, ${result.failed} failed`;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Not connected: onboarding card -->
        <UiCard v-if="!shopify?.connected">
            <template #title>Shopify Sync</template>
            <template #content>
                <div class="flex flex-col gap-5">
                    <p class="text-sm text-surface-600">
                        Connect Fibermade to your Shopify store to keep your
                        yarn catalog, collections, and inventory in sync.
                        Changes you make in Fibermade are automatically pushed
                        to Shopify — no manual updates needed.
                    </p>

                    <ol class="flex flex-col gap-3 text-sm text-surface-700">
                        <li class="flex gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >1</span
                            >
                            <span
                                >Copy your
                                <strong>Fibermade Connect Token</strong> from
                                the Shopify Connection card on the right.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >2</span
                            >
                            <span
                                >Install the <strong>Fibermade app</strong> from
                                the Shopify App Store using the link
                                below.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >3</span
                            >
                            <span
                                >Open the app in Shopify and paste your Connect
                                Token to link your accounts.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >4</span
                            >
                            <span
                                >Return here and use the sync controls to pull
                                your existing Shopify products into
                                Fibermade.</span
                            >
                        </li>
                    </ol>

                    <div v-if="shopifyAppUrl">
                        <a
                            :href="shopifyAppUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline"
                        >
                            Get the Fibermade Shopify App
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>
                        </a>
                    </div>
                </div>
            </template>
        </UiCard>

        <template v-else>
            <!-- Main Shopify Sync card -->
            <UiCard>
                <template #title>Shopify Sync</template>
                <template #content>
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-surface-600">
                            Fibermade is your source of truth. When you update a
                            colorway or collection in Fibermade it's
                            automatically pushed to Shopify, and inventory is
                            kept in sync across both platforms — avoid making
                            product changes directly in Shopify.
                        </p>

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
                            <span class="text-sm text-surface-700">
                                Pulling from Shopify...
                            </span>
                        </div>

                        <UiButton
                            :disabled="isPullRunning || isAnyPullTriggering"
                            :loading="triggeringPullAll && !isPullRunning"
                            @click="pullAll"
                        >
                            Pull All from Shopify
                        </UiButton>
                    </div>
                </template>
            </UiCard>

            <!-- Pull colorways card -->
            <UiCard>
                <template #content>
                    <div class="grid grid-cols-[3fr_2fr] items-center gap-6">
                        <div>
                            <p class="text-sm text-surface-700">
                                Import colorway and product data from your
                                Shopify store into Fibermade.
                            </p>
                            <p class="mt-1 text-xs text-amber-600">
                                This will overwrite existing colorway data in
                                Fibermade with values from Shopify.
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <UiButton
                                outlined
                                :disabled="isPullRunning || isAnyPullTriggering"
                                :loading="triggeringColorways && !isPullRunning"
                                @click="pullColorways"
                            >
                                Pull Colorways
                            </UiButton>
                        </div>
                    </div>
                </template>
            </UiCard>

            <!-- Pull collections card -->
            <UiCard>
                <template #content>
                    <div class="grid grid-cols-[3fr_2fr] items-center gap-6">
                        <div>
                            <p class="text-sm text-surface-700">
                                Import collections from Shopify to match your
                                Fibermade catalog structure.
                            </p>
                            <p class="mt-1 text-xs text-amber-600">
                                This will overwrite existing collection data in
                                Fibermade with values from Shopify.
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <UiButton
                                outlined
                                :disabled="isPullRunning || isAnyPullTriggering"
                                :loading="
                                    triggeringCollections && !isPullRunning
                                "
                                @click="pullCollections"
                            >
                                Pull Collections
                            </UiButton>
                        </div>
                    </div>
                </template>
            </UiCard>

            <!-- Pull inventory card -->
            <UiCard>
                <template #content>
                    <div class="grid grid-cols-[3fr_2fr] items-center gap-6">
                        <div>
                            <p class="text-sm text-surface-700">
                                Import current inventory quantities from Shopify
                                to account for customer purchases made in your
                                store.
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <UiButton
                                outlined
                                :disabled="isPullRunning || isAnyPullTriggering"
                                :loading="triggeringInventory && !isPullRunning"
                                @click="pullInventory"
                            >
                                Pull Inventory
                            </UiButton>
                        </div>
                    </div>
                </template>
            </UiCard>

            <!-- Push all to Shopify card -->
            <UiCard>
                <template #content>
                    <div class="grid grid-cols-[3fr_2fr] items-center gap-6">
                        <div class="flex flex-col gap-2">
                            <p class="text-sm text-surface-700">
                                Push all Fibermade product data — colorways,
                                bases, and collections — to your Shopify store.
                            </p>
                            <p class="text-xs text-amber-600">
                                This will overwrite all product data in your
                                Shopify store with current Fibermade values.
                            </p>

                            <!-- Push last result -->
                            <div
                                v-if="
                                    pushSyncState.status === 'complete' ||
                                    pushSyncState.status === 'failed'
                                "
                                class="rounded-lg border border-surface-200 bg-surface-50 p-3"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-xs font-medium text-surface-700"
                                    >
                                        Last push
                                    </p>
                                    <span
                                        v-if="pushSyncState.status === 'failed'"
                                        class="text-xs font-medium text-red-600"
                                    >
                                        Failed
                                    </span>
                                </div>
                                <p
                                    v-if="pushCompletedAt"
                                    class="text-muted-foreground mt-0.5 text-xs"
                                >
                                    {{ pushCompletedAt }}
                                </p>
                                <div
                                    v-if="pushLastResult"
                                    class="mt-2 flex flex-col gap-1"
                                >
                                    <div
                                        v-if="pushLastResult.colorways"
                                        class="flex justify-between text-xs"
                                    >
                                        <span class="text-surface-600"
                                            >Colorways</span
                                        >
                                        <span class="text-surface-800">
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
                                        <span class="text-surface-600"
                                            >Collections</span
                                        >
                                        <span class="text-surface-800">
                                            {{
                                                formatStepCount(
                                                    pushLastResult.collections,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <p
                                    v-if="pushErrorCount > 0"
                                    class="mt-1 text-xs font-medium text-red-600"
                                >
                                    {{ pushErrorCount }}
                                    {{
                                        pushErrorCount === 1
                                            ? 'error'
                                            : 'errors'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-3">
                            <div
                                v-if="isPushRunning"
                                class="flex items-center gap-2"
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
                                <span class="text-sm text-surface-700">
                                    {{ pushCurrentStepLabel }}
                                </span>
                            </div>
                            <UiButton
                                outlined
                                :disabled="isPushRunning || triggeringPush"
                                :loading="triggeringPush && !isPushRunning"
                                @click="triggerPushAll"
                            >
                                Push All to Shopify
                            </UiButton>
                        </div>
                    </div>
                </template>
            </UiCard>

            <!-- Recent integration log errors -->
            <div
                v-if="shopify.recent_errors && shopify.recent_errors.length > 0"
                class="flex flex-col gap-2"
            >
                <p class="text-xs font-medium text-surface-700">
                    Recent errors
                </p>
                <ul class="flex flex-col gap-1">
                    <li
                        v-for="err in shopify.recent_errors"
                        :key="err.id"
                        class="rounded bg-red-50 px-2 py-1 text-xs text-red-700"
                    >
                        {{ err.message }}
                    </li>
                </ul>
            </div>
        </template>
    </div>
</template>
