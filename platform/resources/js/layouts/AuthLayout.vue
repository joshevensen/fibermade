<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
    pageTitle?: string;
}>();
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />
    <div
        class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogo
                        variant="icon"
                        class="size-9 fill-current text-black dark:text-white"
                    />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <UiCard class="rounded-xl">
                    <template #title>
                        <div class="px-10 pt-8 pb-0 text-center">
                            <h2 class="text-xl">{{ title }}</h2>
                        </div>
                    </template>
                    <template #subtitle>
                        <div class="px-10 pb-0 text-center">
                            {{ description }}
                        </div>
                    </template>
                    <template #content>
                        <div class="px-10 py-8">
                            <slot />
                        </div>
                    </template>
                    <template #footer>
                        <div
                            v-if="$slots.footer"
                            class="text-muted-foreground px-10 pb-8 text-center text-sm"
                        >
                            <slot name="footer" />
                        </div>
                    </template>
                </UiCard>
            </div>
        </div>
    </div>
</template>
