<script setup lang="ts">
import UiLink from '@/components/ui/UiLink.vue';
import type { Component } from 'vue';
import { computed } from 'vue';

interface NavigationLink {
    name: string;
    href: string;
}

interface NavigationSection {
    title: string;
    links: NavigationLink[];
}

interface SocialLink {
    name: string;
    href: string;
    icon: Component;
}

interface Props {
    variant?: 'columns' | 'centered';
    background?: 'white' | 'surface' | 'primary';
    logoUrl?: string;
    description?: string;
    navigationSections?: NavigationSection[];
    mainLinks?: NavigationLink[];
    socialLinks: SocialLink[];
    copyrightText?: string;
    companyName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'columns',
    background: 'white',
    copyrightText: 'All rights reserved.',
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
    <!-- Columns variant -->
    <footer
        v-if="variant === 'columns'"
        :class="[
            backgroundClass,
            'mx-auto max-w-7xl px-6 pt-16 pb-8 sm:pt-24 lg:px-8 lg:pt-32',
        ]"
    >
        <div class="xl:grid xl:grid-cols-3 xl:gap-8">
            <div class="space-y-8">
                <img
                    v-if="logoUrl"
                    class="h-9"
                    :src="logoUrl"
                    :alt="companyName || 'Company name'"
                />
                <p
                    v-if="description"
                    class="text-sm/6 text-balance text-surface-600"
                >
                    {{ description }}
                </p>
                <div class="flex gap-x-6">
                    <UiLink
                        v-for="item in socialLinks"
                        :key="item.name"
                        :href="item.href"
                        class="text-surface-600 hover:text-surface-800"
                    >
                        <span class="sr-only">{{ item.name }}</span>
                        <component
                            :is="item.icon"
                            class="size-6"
                            aria-hidden="true"
                        />
                    </UiLink>
                </div>
            </div>
            <div
                v-if="navigationSections && navigationSections.length > 0"
                class="mt-16 grid grid-cols-2 gap-8 xl:col-span-2 xl:mt-0"
            >
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <template
                        v-for="(section, index) in navigationSections.slice(
                            0,
                            2,
                        )"
                        :key="section.title"
                    >
                        <div :class="{ 'mt-10 md:mt-0': index === 1 }">
                            <h3
                                class="text-sm/6 font-semibold text-surface-900"
                            >
                                {{ section.title }}
                            </h3>
                            <ul role="list" class="mt-6 space-y-4">
                                <li
                                    v-for="link in section.links"
                                    :key="link.name"
                                >
                                    <UiLink
                                        :href="link.href"
                                        class="text-sm/6 text-surface-600 hover:text-surface-900"
                                    >
                                        {{ link.name }}
                                    </UiLink>
                                </li>
                            </ul>
                        </div>
                    </template>
                </div>
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <template
                        v-for="(section, index) in navigationSections.slice(
                            2,
                            4,
                        )"
                        :key="section.title"
                    >
                        <div :class="{ 'mt-10 md:mt-0': index === 1 }">
                            <h3
                                class="text-sm/6 font-semibold text-surface-900"
                            >
                                {{ section.title }}
                            </h3>
                            <ul role="list" class="mt-6 space-y-4">
                                <li
                                    v-for="link in section.links"
                                    :key="link.name"
                                >
                                    <UiLink
                                        :href="link.href"
                                        class="text-sm/6 text-surface-600 hover:text-surface-900"
                                    >
                                        {{ link.name }}
                                    </UiLink>
                                </li>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div
            class="mt-16 border-t border-surface-900/10 pt-8 sm:mt-20 lg:mt-24"
        >
            <p class="text-sm/6 text-surface-600">
                &copy; {{ new Date().getFullYear() }}
                {{ companyName || 'Your Company, Inc.' }}. {{ copyrightText }}
            </p>
        </div>
    </footer>

    <!-- Centered variant -->
    <footer
        v-else
        :class="[
            backgroundClass,
            'mx-auto max-w-7xl overflow-hidden px-6 py-20 sm:py-24 lg:px-8',
        ]"
    >
        <nav
            v-if="mainLinks && mainLinks.length > 0"
            class="-mb-6 flex flex-wrap justify-center gap-x-12 gap-y-3 text-sm/6"
            aria-label="Footer"
        >
            <UiLink
                v-for="item in mainLinks"
                :key="item.name"
                :href="item.href"
                class="text-surface-600 hover:text-surface-900"
            >
                {{ item.name }}
            </UiLink>
        </nav>
        <div class="mt-16 flex justify-center gap-x-10">
            <UiLink
                v-for="item in socialLinks"
                :key="item.name"
                :href="item.href"
                class="text-surface-600 hover:text-surface-800"
            >
                <span class="sr-only">{{ item.name }}</span>
                <component :is="item.icon" class="size-6" aria-hidden="true" />
            </UiLink>
        </div>
        <p class="mt-10 text-center text-sm/6 text-surface-600">
            &copy; {{ new Date().getFullYear() }}
            {{ companyName || 'Your Company, Inc.' }}. {{ copyrightText }}
        </p>
    </footer>
</template>
