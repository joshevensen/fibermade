<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiTag from '@/components/ui/UiTag.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';

interface BaseItem {
    id: number;
    descriptor: string;
    weight: string | null;
    quantity: number;
    unit_price: number | null;
    line_total: number | null;
}

interface ColorwayGroup {
    colorway: {
        id: number;
        name: string;
        primary_image_url: string | null;
    };
    bases: BaseItem[];
}

interface Props {
    id: number;
    order_date: string;
    status: string;
    notes: string | null;
    subtotal_amount: number | null;
    shipping_amount: number | null;
    discount_amount: number | null;
    tax_amount: number | null;
    total_amount: number | null;
    creator: { id: number; name: string };
    skein_count: number;
    colorway_count: number;
    items_by_colorway: ColorwayGroup[];
}

const props = defineProps<Props>();

const STATUS_STEPS = [
    'draft',
    'open',
    'accepted',
    'fulfilled',
    'delivered',
] as const;

function formatEnum(value: string | null | undefined): string {
    if (!value) return '';
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDate(value: string | null | undefined): string {
    if (!value) return '';
    return new Date(value).toLocaleDateString();
}

function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function getOrderStatusSeverity(
    status: string,
): 'success' | 'info' | 'secondary' | 'warn' | 'danger' | 'contrast' {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'open':
            return 'info';
        case 'accepted':
            return 'info';
        case 'fulfilled':
            return 'success';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function isStepCompleted(stepIndex: number): boolean {
    if (props.status === 'cancelled') return false;
    const stepOrder = STATUS_STEPS.indexOf(
        props.status as (typeof STATUS_STEPS)[number],
    );
    if (stepOrder < 0) return false;
    return stepOrder > stepIndex;
}

function isStepActive(stepIndex: number): boolean {
    if (props.status === 'cancelled') return false;
    return STATUS_STEPS[stepIndex] === props.status;
}
</script>

<template>
    <StoreLayout :page-title="`Order — ${props.creator.name}`">
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold">Order Detail</h1>
                <Link :href="`/store/${props.creator.id}/orders`">
                    <UiButton label="Back" severity="secondary" outlined />
                </Link>
            </div>

            <div
                class="flex flex-wrap items-center gap-4 text-sm text-surface-600"
            >
                <span>Order date: {{ formatDate(props.order_date) }}</span>
                <span>Creator: {{ props.creator.name }}</span>
                <span
                    >{{ props.skein_count }} skeins,
                    {{ props.colorway_count }} colorways</span
                >
            </div>

            <div class="flex items-center gap-3">
                <UiTag
                    :severity="getOrderStatusSeverity(props.status)"
                    :value="formatEnum(props.status)"
                />
                <div
                    v-if="props.status === 'cancelled'"
                    class="text-sm font-medium text-surface-600"
                >
                    This order has been cancelled
                </div>
            </div>

            <div
                v-if="props.status !== 'cancelled'"
                class="flex flex-wrap items-center gap-2"
            >
                <template v-for="(step, index) in STATUS_STEPS" :key="step">
                    <span
                        class="rounded px-2 py-1 text-xs font-medium"
                        :class="{
                            'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-200':
                                isStepActive(index),
                            'bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-400':
                                isStepCompleted(index),
                            'bg-surface-50 text-surface-400 dark:bg-surface-900 dark:text-surface-500':
                                !isStepCompleted(index) && !isStepActive(index),
                        }"
                    >
                        {{ formatEnum(step) }}
                    </span>
                    <span
                        v-if="index < STATUS_STEPS.length - 1"
                        class="text-surface-300 dark:text-surface-600"
                    >
                        →
                    </span>
                </template>
            </div>

            <div class="space-y-6">
                <UiCard
                    v-for="group in props.items_by_colorway"
                    :key="group.colorway.id"
                    class="overflow-hidden"
                >
                    <template #title>
                        <div class="flex items-center gap-4">
                            <div
                                class="h-16 w-16 shrink-0 overflow-hidden rounded bg-surface-200"
                            >
                                <img
                                    v-if="group.colorway.primary_image_url"
                                    :src="group.colorway.primary_image_url"
                                    :alt="group.colorway.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-surface-400"
                                >
                                    —
                                </div>
                            </div>
                            <span class="font-medium">{{
                                group.colorway.name
                            }}</span>
                        </div>
                    </template>

                    <template #content>
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:flex-wrap"
                        >
                            <div
                                v-for="base in group.bases"
                                :key="base.id"
                                class="flex min-w-0 flex-1 flex-col gap-1 rounded border border-surface-200 bg-surface-50 p-3 sm:min-w-[180px]"
                            >
                                <div
                                    class="text-sm font-medium text-surface-700"
                                >
                                    {{ base.descriptor }}
                                    <span
                                        v-if="base.weight"
                                        class="font-normal text-surface-500"
                                    >
                                        ({{ formatEnum(base.weight) }})
                                    </span>
                                </div>
                                <div class="text-sm text-surface-600">
                                    Qty: {{ base.quantity }} ×
                                    {{ formatCurrency(base.unit_price) }} =
                                    {{ formatCurrency(base.line_total) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </UiCard>
            </div>

            <UiCard>
                <template #title>Order summary</template>
                <template #content>
                    <div class="space-y-4">
                        <div
                            v-if="props.notes"
                            class="rounded border border-surface-200 bg-surface-50 p-3 text-sm"
                        >
                            <span class="font-medium text-surface-700"
                                >Order notes:</span
                            >
                            <p class="mt-1 text-surface-600">
                                {{ props.notes }}
                            </p>
                        </div>

                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-surface-600">Subtotal</span>
                                <span>{{
                                    formatCurrency(props.subtotal_amount)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-surface-600">Shipping</span>
                                <span>{{
                                    formatCurrency(props.shipping_amount ?? 0)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-surface-600">Discount</span>
                                <span>{{
                                    formatCurrency(props.discount_amount ?? 0)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-surface-600">Tax</span>
                                <span>{{
                                    formatCurrency(props.tax_amount ?? 0)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t border-surface-200 pt-2 font-medium"
                            >
                                <span>Total</span>
                                <span>{{
                                    formatCurrency(props.total_amount)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </UiCard>
        </div>
    </StoreLayout>
</template>
