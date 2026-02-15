<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

interface Props {
    label: string;
    count: number;
    href: string;
    icon?: Component;
}

defineProps<Props>();
</script>

<template>
    <Link :href="href" class="block">
        <UiCard
            class="transition-colors focus-within:border-primary-400 focus-within:ring-2 focus-within:ring-primary-200 hover:border-primary-300 hover:bg-surface-50 dark:focus-within:ring-primary-900 dark:hover:bg-surface-100"
            tabindex="-1"
        >
            <template #content>
                <div class="flex items-center gap-3">
                    <component
                        :is="icon"
                        v-if="icon"
                        class="size-8 shrink-0 text-surface-500"
                        aria-hidden
                    />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-surface-600">
                            {{ label }}
                        </p>
                        <p class="text-2xl font-semibold text-surface-900">
                            {{ count }}
                        </p>
                    </div>
                </div>
            </template>
        </UiCard>
    </Link>
</template>
