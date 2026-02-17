<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        pageTitle?: string;
        wide?: boolean;
    }>(),
    { wide: false },
);
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />
    <div
        class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-3 md:p-6"
    >
        <div
            :class="[
                'flex w-full flex-col gap-6',
                wide ? 'max-w-4xl' : 'max-w-md',
            ]"
        >
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <AppLogo class="w-48" />
            </Link>

            <div class="flex flex-col gap-6">
                <UiCard class="rounded-xl">
                    <template #title>
                        <div class="px-4 pt-2 pb-0">
                            <h2 class="text-2xl font-bold">{{ title }}</h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="px-4 pt-4 pb-8">
                            <slot />
                        </div>
                    </template>
                    <template #footer>
                        <div
                            v-if="$slots.footer"
                            class="text-muted-foreground px-4 pb-2 text-center text-sm"
                        >
                            <slot name="footer" />
                        </div>
                    </template>
                </UiCard>
            </div>
        </div>
    </div>
</template>
