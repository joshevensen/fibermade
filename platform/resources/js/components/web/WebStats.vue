<script setup lang="ts">
import { computed } from 'vue';

interface StatItem {
    id: number | string;
    name: string;
    value: string;
}

interface Props {
    variant?: 'grid' | 'simple';
    background?: 'white' | 'surface' | 'primary';
    title?: string;
    description?: string;
    stats: StatItem[];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'simple',
    background: 'white',
});

const backgroundClass = computed(() => {
    switch (props.background) {
        case 'surface':
            return 'bg-surface-200';
        case 'primary':
            return 'bg-primary-500';
        default:
            return 'bg-white';
    }
});
</script>

<template>
    <div :class="[backgroundClass, 'py-24 sm:py-32']">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <!-- Grid variant with title/description -->
            <div
                v-if="variant === 'grid'"
                class="mx-auto max-w-2xl lg:max-w-none"
            >
                <div class="text-center">
                    <h2
                        v-if="title"
                        class="text-4xl font-semibold tracking-tight text-balance text-surface-900 sm:text-5xl"
                    >
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="mt-4 text-lg/8 text-surface-600"
                    >
                        {{ description }}
                    </p>
                </div>
                <dl
                    class="mt-16 grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div
                        v-for="stat in stats"
                        :key="stat.id"
                        class="flex flex-col bg-surface-400/5 p-8"
                    >
                        <dt class="text-sm/6 font-semibold text-surface-600">
                            {{ stat.name }}
                        </dt>
                        <dd
                            class="order-first text-3xl font-semibold tracking-tight text-surface-900"
                        >
                            {{ stat.value }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Simple variant -->
            <dl
                v-else
                class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-3"
            >
                <div
                    v-for="stat in stats"
                    :key="stat.id"
                    class="mx-auto flex max-w-xs flex-col gap-y-4"
                >
                    <dt class="text-base/7 text-surface-600">
                        {{ stat.name }}
                    </dt>
                    <dd
                        class="order-first text-3xl font-semibold tracking-tight text-surface-900 sm:text-5xl"
                    >
                        {{ stat.value }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
