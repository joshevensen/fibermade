<script setup lang="ts">
import { edit as editColorway } from '@/actions/App/Http/Controllers/ColorwayController';
import UiCard from '@/components/ui/UiCard.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Colorway {
    id: number;
    name: string;
    per_pan: number;
}

interface Base {
    id: number;
    descriptor: string;
}

interface DyeListItem {
    colorway: Colorway;
    base: Base;
    quantity: number;
}

interface Props {
    dyeList: DyeListItem[];
}

const props = defineProps<Props>();

const groupBy = ref<'colorway' | 'base'>('colorway');

const groupByOptions = [
    { label: 'Colorway', value: 'colorway' },
    { label: 'Base', value: 'base' },
];

const groupedByColorway = computed(() => {
    const grouped = new Map<
        number,
        {
            colorway: Colorway;
            bases: Map<number, { base: Base; quantity: number }>;
        }
    >();

    props.dyeList.forEach((item) => {
        if (!grouped.has(item.colorway.id)) {
            grouped.set(item.colorway.id, {
                colorway: item.colorway,
                bases: new Map(),
            });
        }

        const group = grouped.get(item.colorway.id)!;
        if (!group.bases.has(item.base.id)) {
            group.bases.set(item.base.id, {
                base: item.base,
                quantity: 0,
            });
        }

        const baseGroup = group.bases.get(item.base.id)!;
        baseGroup.quantity += item.quantity;
    });

    return Array.from(grouped.values()).map((group) => ({
        colorway: group.colorway,
        bases: Array.from(group.bases.values()),
    }));
});

const groupedByBase = computed(() => {
    const grouped = new Map<
        number,
        {
            base: Base;
            colorways: Map<number, { colorway: Colorway; quantity: number }>;
        }
    >();

    props.dyeList.forEach((item) => {
        if (!grouped.has(item.base.id)) {
            grouped.set(item.base.id, {
                base: item.base,
                colorways: new Map(),
            });
        }

        const group = grouped.get(item.base.id)!;
        if (!group.colorways.has(item.colorway.id)) {
            group.colorways.set(item.colorway.id, {
                colorway: item.colorway,
                quantity: 0,
            });
        }

        const colorwayGroup = group.colorways.get(item.colorway.id)!;
        colorwayGroup.quantity += item.quantity;
    });

    return Array.from(grouped.values()).map((group) => ({
        base: group.base,
        colorways: Array.from(group.colorways.values()),
    }));
});

const totalSkeins = computed(() => {
    return props.dyeList.reduce((sum, item) => sum + item.quantity, 0);
});

const pansPerItem = computed(() => {
    return props.dyeList.map((item) => {
        const perPan = item.colorway.per_pan || 1;
        return Math.ceil(item.quantity / perPan);
    });
});

const totalPans = computed(() => {
    return pansPerItem.value.reduce((sum, pans) => sum + pans, 0);
});

function getPansForItem(quantity: number, perPan: number): number {
    return Math.ceil(quantity / (perPan || 1));
}

function handleColorwayClick(colorway: Colorway, event: Event): void {
    event.preventDefault();
    router.visit(editColorway.url(colorway.id));
}
</script>

<template>
    <UiCard>
        <template #title> Dye List </template>
        <template #content>
            <div class="space-y-4">
                <div v-if="dyeList.length === 0" class="text-surface-500">
                    No items in dye list
                </div>
                <div v-else>
                    <!-- Header with GroupBy and Totals -->
                    <div
                        class="mb-4 flex items-center justify-between border-b border-surface-200 pb-3"
                    >
                        <UiSelectButton
                            v-model="groupBy"
                            :options="groupByOptions"
                        />
                        <div class="flex gap-6">
                            <div class="text-right">
                                <div class="text-sm text-surface-600">
                                    Total Skeins
                                </div>
                                <div
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ totalSkeins }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-surface-600">
                                    Total Pans
                                </div>
                                <div
                                    class="text-lg font-semibold text-surface-900"
                                >
                                    {{ totalPans }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Grouped by Colorway -->
                    <div v-if="groupBy === 'colorway'" class="space-y-4">
                        <div
                            v-for="group in groupedByColorway"
                            :key="group.colorway.id"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center justify-between border-b border-surface-200 pb-2"
                            >
                                <a
                                    :href="editColorway.url(group.colorway.id)"
                                    class="font-medium text-primary-600 hover:text-primary-700 hover:underline"
                                    @click="
                                        handleColorwayClick(
                                            group.colorway,
                                            $event,
                                        )
                                    "
                                >
                                    {{ group.colorway.name }}
                                </a>
                            </div>
                            <div
                                v-for="baseItem in group.bases"
                                :key="baseItem.base.id"
                                class="ml-4 flex items-center justify-between border-b border-surface-200 pb-2 last:border-0 last:pb-0"
                            >
                                <div class="flex-1">
                                    <div class="text-sm text-surface-600">
                                        {{ baseItem.base.descriptor }}
                                    </div>
                                </div>
                                <div class="ml-4 flex gap-4 text-right">
                                    <div>
                                        <div class="text-xs text-surface-500">
                                            Skeins
                                        </div>
                                        <div
                                            class="text-lg font-semibold text-surface-900"
                                        >
                                            {{ baseItem.quantity }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-surface-500">
                                            Pans
                                        </div>
                                        <div
                                            class="text-lg font-semibold text-surface-900"
                                        >
                                            {{
                                                getPansForItem(
                                                    baseItem.quantity,
                                                    group.colorway.per_pan,
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Grouped by Base -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="group in groupedByBase"
                            :key="group.base.id"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center justify-between border-b border-surface-200 pb-2"
                            >
                                <div class="font-medium text-surface-900">
                                    {{ group.base.descriptor }}
                                </div>
                            </div>
                            <div
                                v-for="colorwayItem in group.colorways"
                                :key="colorwayItem.colorway.id"
                                class="ml-4 flex items-center justify-between border-b border-surface-200 pb-2 last:border-0 last:pb-0"
                            >
                                <div class="flex-1">
                                    <a
                                        :href="
                                            editColorway.url(
                                                colorwayItem.colorway.id,
                                            )
                                        "
                                        class="text-sm text-primary-600 hover:text-primary-700 hover:underline"
                                        @click="
                                            handleColorwayClick(
                                                colorwayItem.colorway,
                                                $event,
                                            )
                                        "
                                    >
                                        {{ colorwayItem.colorway.name }}
                                    </a>
                                </div>
                                <div class="ml-4 flex gap-4 text-right">
                                    <div>
                                        <div class="text-xs text-surface-500">
                                            Skeins
                                        </div>
                                        <div
                                            class="text-lg font-semibold text-surface-900"
                                        >
                                            {{ colorwayItem.quantity }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-surface-500">
                                            Pans
                                        </div>
                                        <div
                                            class="text-lg font-semibold text-surface-900"
                                        >
                                            {{
                                                getPansForItem(
                                                    colorwayItem.quantity,
                                                    colorwayItem.colorway
                                                        .per_pan,
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </UiCard>
</template>
