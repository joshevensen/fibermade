<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiIcon from '@/components/ui/UiIcon.vue';
import { IconList } from '@/composables/useIcon';
import { toUrl, urlIsActive } from '@/lib/utils';
import { logout } from '@/routes';
import type { NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';

interface Props {
    collapsed: boolean;
    navItems: Array<{
        title: string;
        href: NonNullable<NavItem['href']>;
        icon?: string | Component;
    }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
}>();

const page = usePage();

const currentUrl = computed(() => page.url);

function toggleCollapse() {
    emit('update:collapsed', !props.collapsed);
}

function isActive(item: { href: NonNullable<NavItem['href']> }) {
    return urlIsActive(item.href, currentUrl.value);
}

function handleLogout() {
    router.post(logout.url());
}
</script>

<template>
    <aside
        :class="[
            'sticky top-0 hidden h-screen flex-col border-r border-surface-200 bg-surface-100 transition-all duration-300 lg:flex',
            collapsed ? 'w-16' : 'w-40',
        ]"
    >
        <div class="flex h-12 items-center justify-center px-2">
            <AppLogo v-if="collapsed" variant="icon" class="size-8" />
            <AppLogo v-else variant="full" />
        </div>

        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-2">
                <Link
                    v-for="item in navItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-bold transition-colors hover:bg-surface-200',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <UiIcon
                        v-if="item.icon"
                        :name="
                            typeof item.icon === 'string'
                                ? item.icon
                                : undefined
                        "
                        :component="
                            typeof item.icon !== 'string'
                                ? (item.icon as Component)
                                : undefined
                        "
                        :class="
                            isActive(item) ? 'text-primary' : 'text-surface-400'
                        "
                    />
                    <span
                        v-if="!collapsed"
                        :class="[
                            isActive(item)
                                ? 'text-primary'
                                : 'text-surface-500',
                            'flex-1',
                        ]"
                        >{{ item.title }}</span
                    >
                </Link>
            </nav>
        </div>

        <div
            :class="[
                'flex justify-between p-4',
                collapsed ? 'flex-col-reverse gap-4' : 'flex-row',
            ]"
        >
            <UiButton
                :icon="collapsed ? IconList.Right : IconList.Left"
                text
                class="w-full justify-center"
                @click="toggleCollapse"
            />

            <UiButton
                :icon="IconList.SignOut"
                text
                class="w-full justify-center"
                @click="handleLogout"
            />
        </div>
    </aside>
</template>
