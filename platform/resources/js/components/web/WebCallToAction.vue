<script setup lang="ts">
import UiLink from '@/components/ui/UiLink.vue';

interface ButtonConfig {
    text: string;
    href: string;
}

interface Props {
    variant?: 'justified' | 'centered' | 'stacked';
    title: string;
    description?: string;
    primaryButton: ButtonConfig;
    secondaryButton?: ButtonConfig;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'centered',
});
</script>

<template>
    <!-- Justified variant: side-by-side layout -->
    <div
        v-if="variant === 'justified'"
        class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:flex lg:items-center lg:justify-between lg:px-8"
    >
        <h2
            class="max-w-2xl text-4xl font-semibold tracking-tight text-surface-900 sm:text-5xl"
        >
            {{ title }}
        </h2>
        <div class="mt-10 flex items-center gap-x-6 lg:mt-0 lg:shrink-0">
            <UiLink
                :href="primaryButton.href"
                class="rounded-md bg-primary-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            >
                {{ primaryButton.text }}
            </UiLink>
            <UiLink
                v-if="secondaryButton"
                :href="secondaryButton.href"
                class="text-sm/6 font-semibold text-surface-900 hover:opacity-80"
            >
                {{ secondaryButton.text }}
                <span aria-hidden="true">→</span>
            </UiLink>
        </div>
    </div>

    <!-- Centered variant: everything centered with description -->
    <div v-else-if="variant === 'centered'" class="px-6 py-24 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2
                class="text-4xl font-semibold tracking-tight text-balance text-surface-900 sm:text-5xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mx-auto mt-6 max-w-xl text-lg/8 text-pretty text-surface-600"
            >
                {{ description }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <UiLink
                    :href="primaryButton.href"
                    class="rounded-md bg-primary-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    {{ primaryButton.text }}
                </UiLink>
                <UiLink
                    v-if="secondaryButton"
                    :href="secondaryButton.href"
                    class="text-sm/6 font-semibold text-surface-900"
                >
                    {{ secondaryButton.text }}
                    <span aria-hidden="true">→</span>
                </UiLink>
            </div>
        </div>
    </div>

    <!-- Stacked variant: title and buttons stacked -->
    <div v-else class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
        <h2
            class="max-w-2xl text-4xl font-semibold tracking-tight text-balance text-surface-900 sm:text-5xl"
        >
            {{ title }}
        </h2>
        <div class="mt-10 flex items-center gap-x-6">
            <UiLink
                :href="primaryButton.href"
                class="rounded-md bg-primary-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            >
                {{ primaryButton.text }}
            </UiLink>
            <UiLink
                v-if="secondaryButton"
                :href="secondaryButton.href"
                class="text-sm/6 font-semibold text-surface-900"
            >
                {{ secondaryButton.text }}
                <span aria-hidden="true">→</span>
            </UiLink>
        </div>
    </div>
</template>
