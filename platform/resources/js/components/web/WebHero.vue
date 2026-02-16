<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    background?: 'white' | 'surface' | 'primary';
    subtitle?: string;
    title: string;
    description?: string;
    primaryButton?: ButtonConfig;
    secondaryButton?: ButtonConfig;
    announcement?: AnnouncementConfig;
    badge?: BadgeConfig;
    logoUrl?: string;
    imageTiles?: ImageTile[];
    screenshotUrl?: string;
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
            return 'bg-surface-50';
    }
});

const { IconList } = useIcon();
</script>

<template>
    <!-- WithImageTiles variant -->
    <div
        v-if="variant === 'withImageTiles'"
        :class="[backgroundClass, 'relative isolate']"
    >
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
                                class="relative rounded-full px-3 py-1 text-sm/6 text-surface-600 ring-1 ring-surface-900/10 hover:ring-surface-900/20"
                            >
                                {{ announcement.text }}
                                <UiButton
                                    type="button"
                                    text
                                    class="p-0 font-semibold text-primary-500"
                                    @click="router.visit(announcement.link)"
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
                                </UiButton>
                            </div>
                        </div>
                        <h1
                            class="text-5xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-7xl"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="mt-8 text-lg font-medium text-pretty text-surface-500 sm:max-w-md sm:text-xl/8 lg:max-w-none"
                        >
                            {{ description }}
                        </p>
                        <div
                            v-if="primaryButton || secondaryButton"
                            class="mt-10 flex items-center gap-x-6"
                        >
                            <UiButton
                                v-if="primaryButton"
                                type="button"
                                severity="primary"
                                @click="router.visit(primaryButton.href)"
                            >
                                {{ primaryButton.text }}
                            </UiButton>
                            <UiButton
                                v-if="secondaryButton"
                                type="button"
                                text
                                class="text-sm/6 font-semibold text-surface-900"
                                @click="router.visit(secondaryButton.href)"
                            >
                                {{ secondaryButton.text }}
                                <span aria-hidden="true">→</span>
                            </UiButton>
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
                                    class="aspect-2/3 w-full rounded-xl bg-surface-900/5 object-cover shadow-lg"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-surface-900/10 ring-inset"
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
                                    class="aspect-2/3 w-full rounded-xl bg-surface-900/5 object-cover shadow-lg"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-surface-900/10 ring-inset"
                                ></div>
                            </div>
                            <div class="relative">
                                <img
                                    :src="imageTiles[2].url"
                                    :alt="imageTiles[2].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-surface-900/5 object-cover shadow-lg"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-surface-900/10 ring-inset"
                                ></div>
                            </div>
                        </div>
                        <div class="w-44 flex-none space-y-8 pt-32 sm:pt-0">
                            <div class="relative">
                                <img
                                    :src="imageTiles[3].url"
                                    :alt="imageTiles[3].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-surface-900/5 object-cover shadow-lg"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-surface-900/10 ring-inset"
                                ></div>
                            </div>
                            <div class="relative">
                                <img
                                    :src="imageTiles[4].url"
                                    :alt="imageTiles[4].alt || ''"
                                    class="aspect-2/3 w-full rounded-xl bg-surface-900/5 object-cover shadow-lg"
                                />
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-surface-900/10 ring-inset"
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
        :class="[backgroundClass, 'relative isolate px-6 pt-14 lg:px-8']"
    >
        <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
            <div
                v-if="announcement"
                class="hidden sm:mb-8 sm:flex sm:justify-center"
            >
                <div
                    class="relative rounded-full px-3 py-1 text-sm/6 text-surface-600 ring-1 ring-surface-900/10 hover:ring-surface-900/20"
                >
                    {{ announcement.text }}
                    <UiButton
                        type="button"
                        text
                        class="p-0 font-semibold text-primary-500"
                        @click="router.visit(announcement.link)"
                    >
                        <span class="absolute inset-0" aria-hidden="true"></span
                        >Read more
                        <span aria-hidden="true">&rarr;</span>
                    </UiButton>
                </div>
            </div>
            <div class="text-center">
                <h1
                    class="text-5xl font-semibold tracking-tight text-balance text-surface-900 sm:text-7xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-8 text-lg font-medium text-pretty text-surface-500 sm:text-xl/8"
                >
                    {{ description }}
                </p>
                <div
                    v-if="primaryButton || secondaryButton"
                    class="mt-10 flex items-center justify-center gap-x-6"
                >
                    <UiButton
                        v-if="primaryButton"
                        type="button"
                        severity="primary"
                        @click="router.visit(primaryButton.href)"
                    >
                        {{ primaryButton.text }}
                    </UiButton>
                    <UiButton
                        v-if="secondaryButton"
                        type="button"
                        text
                        class="text-sm/6 font-semibold text-surface-900"
                        @click="router.visit(secondaryButton.href)"
                    >
                        {{ secondaryButton.text }}
                        <span aria-hidden="true">→</span>
                    </UiButton>
                </div>
            </div>
        </div>
    </div>

    <!-- Page variant -->
    <div
        v-else-if="variant === 'page'"
        :class="[backgroundClass, 'px-6 py-24 sm:py-32 lg:px-8']"
    >
        <div class="mx-auto max-w-2xl text-center">
            <p
                v-if="subtitle"
                class="text-base/7 font-semibold text-primary-500"
            >
                {{ subtitle }}
            </p>
            <h2
                class="mt-2 text-5xl font-semibold tracking-tight text-surface-900 sm:text-7xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mt-8 text-lg font-medium text-pretty text-surface-500 sm:text-xl/8"
            >
                {{ description }}
            </p>
        </div>
    </div>

    <!-- ScreenshotRight variant -->
    <div v-else :class="[backgroundClass, 'overflow-hidden']">
        <div
            class="mx-auto flex max-w-7xl flex-col px-6 py-6 lg:flex-row lg:px-8 lg:py-8"
        >
            <div class="max-w-2xl min-w-0 shrink-0">
                <img
                    v-if="logoUrl"
                    class="h-11"
                    :src="logoUrl"
                    alt="Your Company"
                />
                <div v-if="badge" class="mt-24 sm:mt-32 lg:mt-16">
                    <UiButton
                        v-if="badge.link"
                        type="button"
                        text
                        class="inline-flex space-x-6 p-0"
                        @click="router.visit(badge.link)"
                    >
                        <span
                            class="rounded-full bg-primary-50 px-3 py-1 text-sm/6 font-semibold text-primary-500 ring-1 ring-primary-500/20 ring-inset"
                        >
                            {{ badge.label }}
                        </span>
                        <span
                            class="inline-flex items-center space-x-2 text-sm/6 font-medium text-surface-600"
                        >
                            <span>{{ badge.text }}</span>
                            <i
                                :class="[
                                    IconList.Right,
                                    'size-5 text-surface-400',
                                ]"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </UiButton>
                    <span
                        v-else
                        class="rounded-full bg-primary-50 px-3 py-1 text-sm/6 font-semibold text-primary-500 ring-1 ring-primary-500/20 ring-inset"
                    >
                        {{ badge.label }}
                    </span>
                </div>
                <h1
                    class="mt-10 text-5xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-7xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-8 text-lg font-medium text-pretty text-surface-500 sm:text-xl/8"
                >
                    {{ description }}
                </p>
                <div
                    v-if="primaryButton || secondaryButton"
                    class="mt-10 flex items-center gap-x-6"
                >
                    <UiButton
                        v-if="primaryButton"
                        type="button"
                        severity="primary"
                        @click="router.visit(primaryButton.href)"
                    >
                        {{ primaryButton.text }}
                    </UiButton>
                    <UiButton
                        v-if="secondaryButton"
                        type="button"
                        text
                        class="text-sm/6 font-semibold text-surface-900"
                        @click="router.visit(secondaryButton.href)"
                    >
                        {{ secondaryButton.text }}
                        <span aria-hidden="true">→</span>
                    </UiButton>
                </div>
            </div>
            <div
                v-if="screenshotUrl"
                class="mx-auto mt-16 flex max-w-2xl min-w-0 sm:mt-24 lg:mx-0 lg:mt-0 lg:ml-10 lg:max-w-none lg:flex-none xl:ml-32"
            >
                <div class="w-full flex-none lg:max-w-3xl xl:max-w-4xl">
                    <img
                        :src="screenshotUrl"
                        alt="App screenshot"
                        class="w-full max-w-full rounded-md bg-surface-50 object-cover shadow-xl ring-1 ring-surface-900/10"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
