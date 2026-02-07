<script setup lang="ts">
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';

interface ImageTile {
    url: string;
    alt?: string;
}

interface AnnouncementConfig {
    text: string;
    link: string;
}

interface BadgeConfig {
    label: string;
    text: string;
    link?: string;
}

interface ButtonConfig {
    text: string;
    href: string;
}

interface Props {
    variant?: 'withImageTiles' | 'simple' | 'page' | 'screenshotRight';
    subtitle?: string;
    title: string;
    description?: string;
    primaryButton?: ButtonConfig;
    secondaryButton?: ButtonConfig;
    announcement?: AnnouncementConfig;
    badge?: BadgeConfig;
    logoUrlLight?: string;
    logoUrlDark?: string;
    imageTiles?: ImageTile[];
    screenshotUrlLight?: string;
    screenshotUrlDark?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'simple',
});

const { IconList } = useIcon();
</script>

<template>
    <!-- WithImageTiles variant -->
    <div v-if="variant === 'withImageTiles'" class="relative isolate">
        <div class="overflow-hidden">
            <div
                class="mx-auto max-w-7xl px-6 pt-36 pb-32 sm:pt-60 lg:px-8 lg:pt-32"
            >
                <div
                    class="mx-auto max-w-2xl gap-x-14 lg:mx-0 lg:flex lg:max-w-none lg:items-center"
                >
                    <div
                        class="relative w-full lg:max-w-xl lg:shrink-0 xl:max-w-2xl"
                    >
                        <div
                            v-if="announcement"
                            class="hidden sm:mb-8 sm:flex sm:justify-start"
                        >
                            <div
                                class="relative rounded-full px-3 py-1 text-sm/6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20 dark:text-gray-400 dark:ring-white/10 dark:hover:ring-white/20"
                            >
                                {{ announcement.text }}
                                <UiLink
                                    :href="announcement.link"
                                    class="font-semibold text-indigo-600 dark:text-indigo-400"
                                >
                                    <span
                                        class="absolute inset-0"
                                        aria-hidden="true"
                                    ></span
                                    >Read more
                                    <i
                                        :class="[IconList.Right, 'ml-1']"
                                        aria-hidden="true"
                                    ></i>
                                </UiLink>
                            </div>
                        </div>
                        <h1
                            class="text-5xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-7xl dark:text-white"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:max-w-md sm:text-xl/8 lg:max-w-none dark:text-gray-400"
                        >
                            {{ description }}
                        </p>
                        <div
                            v-if="primaryButton || secondaryButton"
                            class="mt-10 flex items-center gap-x-6"
                        >
                            <UiLink
                                v-if="primaryButton"
                                :href="primaryButton.href"
                                class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                            >
                                {{ primaryButton.text }}
                            </UiLink>
                            <UiLink
                                v-if="secondaryButton"
                                :href="secondaryButton.href"
                                class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                            >
                                {{ secondaryButton.text }}
                                <span aria-hidden="true">→</span>
                            </UiLink>
                        </div>
                    </div>
                    <div
                        v-if="imageTiles && imageTiles.length >= 5"
                        class="mt-14 flex justify-end gap-8 sm:-mt-44 sm:justify-start sm:pl-20 lg:mt-0 lg:pl-0"
                    >
                        <div
                            class="ml-auto w-44 flex-none space-y-8 pt-32 sm:ml-0 sm:pt-80 lg:order-last lg:pt-36 xl:order-0 xl:pt-80"
                        >
                            <div class="relative">
                                <img
                                    :src="imageTiles[0].url"
                                    :alt="imageTiles[0].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-gray-900/5 object-cover shadow-lg dark:bg-gray-700/5"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-gray-900/10 ring-inset dark:ring-white/10"
                                ></div>
                            </div>
                        </div>
                        <div
                            class="mr-auto w-44 flex-none space-y-8 sm:mr-0 sm:pt-52 lg:pt-36"
                        >
                            <div class="relative">
                                <img
                                    :src="imageTiles[1].url"
                                    :alt="imageTiles[1].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-gray-900/5 object-cover shadow-lg dark:bg-gray-700/5"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-gray-900/10 ring-inset dark:ring-white/10"
                                ></div>
                            </div>
                            <div class="relative">
                                <img
                                    :src="imageTiles[2].url"
                                    :alt="imageTiles[2].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-gray-900/5 object-cover shadow-lg dark:bg-gray-700/5"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-gray-900/10 ring-inset dark:ring-white/10"
                                ></div>
                            </div>
                        </div>
                        <div class="w-44 flex-none space-y-8 pt-32 sm:pt-0">
                            <div class="relative">
                                <img
                                    :src="imageTiles[3].url"
                                    :alt="imageTiles[3].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-gray-900/5 object-cover shadow-lg dark:bg-gray-700/5"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-gray-900/10 ring-inset dark:ring-white/10"
                                ></div>
                            </div>
                            <div class="relative">
                                <img
                                    :src="imageTiles[4].url"
                                    :alt="imageTiles[4].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-gray-900/5 object-cover shadow-lg dark:bg-gray-700/5"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-gray-900/10 ring-inset dark:ring-white/10"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple variant -->
    <div
        v-else-if="variant === 'simple'"
        class="relative isolate px-6 pt-14 lg:px-8"
    >
        <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
            <div
                v-if="announcement"
                class="hidden sm:mb-8 sm:flex sm:justify-center"
            >
                <div
                    class="relative rounded-full px-3 py-1 text-sm/6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20 dark:text-gray-400 dark:ring-white/10 dark:hover:ring-white/20"
                >
                    {{ announcement.text }}
                    <UiLink
                        :href="announcement.link"
                        class="font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        <span class="absolute inset-0" aria-hidden="true"></span
                        >Read more
                        <span aria-hidden="true">&rarr;</span>
                    </UiLink>
                </div>
            </div>
            <div class="text-center">
                <h1
                    class="text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl dark:text-white"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-gray-400"
                >
                    {{ description }}
                </p>
                <div
                    v-if="primaryButton || secondaryButton"
                    class="mt-10 flex items-center justify-center gap-x-6"
                >
                    <UiLink
                        v-if="primaryButton"
                        :href="primaryButton.href"
                        class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                    >
                        {{ primaryButton.text }}
                    </UiLink>
                    <UiLink
                        v-if="secondaryButton"
                        :href="secondaryButton.href"
                        class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                    >
                        {{ secondaryButton.text }}
                        <span aria-hidden="true">→</span>
                    </UiLink>
                </div>
            </div>
        </div>
    </div>

    <!-- Page variant -->
    <div v-else-if="variant === 'page'" class="px-6 py-24 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p
                v-if="subtitle"
                class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
            >
                {{ subtitle }}
            </p>
            <h2
                class="mt-2 text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl dark:text-white"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-gray-400"
            >
                {{ description }}
            </p>
        </div>
    </div>

    <!-- ScreenshotRight variant -->
    <div
        v-else
        class="mx-auto max-w-7xl px-6 pt-10 pb-24 sm:pb-32 lg:flex lg:px-8 lg:py-40"
    >
        <div class="mx-auto max-w-2xl shrink-0 lg:mx-0 lg:pt-8">
            <img
                v-if="logoUrlLight"
                class="h-11 dark:hidden"
                :src="logoUrlLight"
                alt="Your Company"
            />
            <img
                v-if="logoUrlDark"
                class="h-11 not-dark:hidden"
                :src="logoUrlDark"
                alt="Your Company"
            />
            <div v-if="badge" class="mt-24 sm:mt-32 lg:mt-16">
                <UiLink
                    v-if="badge.link"
                    :href="badge.link"
                    class="inline-flex space-x-6"
                >
                    <span
                        class="rounded-full bg-indigo-50 px-3 py-1 text-sm/6 font-semibold text-indigo-600 ring-1 ring-indigo-600/20 ring-inset dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/25"
                    >
                        {{ badge.label }}
                    </span>
                    <span
                        class="inline-flex items-center space-x-2 text-sm/6 font-medium text-gray-600 dark:text-gray-300"
                    >
                        <span>{{ badge.text }}</span>
                        <i
                            :class="[
                                IconList.Right,
                                'size-5 text-gray-400 dark:text-gray-500',
                            ]"
                            aria-hidden="true"
                        ></i>
                    </span>
                </UiLink>
            </div>
            <h1
                class="mt-10 text-5xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-7xl dark:text-white"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-gray-400"
            >
                {{ description }}
            </p>
            <div
                v-if="primaryButton || secondaryButton"
                class="mt-10 flex items-center gap-x-6"
            >
                <UiLink
                    v-if="primaryButton"
                    :href="primaryButton.href"
                    class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                >
                    {{ primaryButton.text }}
                </UiLink>
                <UiLink
                    v-if="secondaryButton"
                    :href="secondaryButton.href"
                    class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                >
                    {{ secondaryButton.text }}
                    <span aria-hidden="true">→</span>
                </UiLink>
            </div>
        </div>
        <div
            v-if="screenshotUrlLight || screenshotUrlDark"
            class="mx-auto mt-16 flex max-w-2xl sm:mt-24 lg:mt-0 lg:mr-0 lg:ml-10 lg:max-w-none lg:flex-none xl:ml-32"
        >
            <div class="max-w-3xl flex-none sm:max-w-5xl lg:max-w-none">
                <img
                    v-if="screenshotUrlLight"
                    :src="screenshotUrlLight"
                    alt="App screenshot"
                    class="w-304 rounded-md bg-gray-50 shadow-xl ring-1 ring-gray-900/10 dark:hidden"
                />
                <img
                    v-if="screenshotUrlDark"
                    :src="screenshotUrlDark"
                    alt="App screenshot"
                    class="w-304 rounded-md bg-white/5 shadow-2xl ring-1 ring-white/10 not-dark:hidden"
                />
            </div>
        </div>
    </div>
</template>
