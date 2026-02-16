<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiIcon from '@/components/ui/UiIcon.vue';
import { useIcon } from '@/composables/useIcon';
import { toUrl, urlIsActive } from '@/lib/utils';
import { logout } from '@/routes';
import type { NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';

interface Props {
    visible: boolean;
    navItems: Array<{
        title: string;
        href: NonNullable<NavItem['href']>;
        icon?: string | Component;
    }>;
}

defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

const page = usePage();
const currentUrl = computed(() => page.url);
const { IconList } = useIcon();

function closeDrawer() {
    emit('update:visible', false);
}

function isActive(item: { href: NonNullable<NavItem['href']> }) {
    return urlIsActive(item.href, currentUrl.value);
}

function handleLogout() {
    router.post(logout.url());
    closeDrawer();
}
</script>

<template>
    <UiDrawer
        :visible="visible"
        position="left"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <AppLogo />
        </template>

        <nav class="space-y-1 px-2">
            <Link
                v-for="item in navItems"
                :key="toUrl(item.href)"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-bold transition-colors hover:bg-surface-200',
                ]"
                @click="closeDrawer"
            >
                <UiIcon
                    v-if="item.icon"
                    :name="
                        typeof item.icon === 'string' ? item.icon : undefined
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
                    :class="[
                        isActive(item) ? 'text-primary' : 'text-surface-500',
                        'flex-1',
                    ]"
                    >{{ item.title }}</span
                >
            </Link>
        </nav>

        <template #footer>
            <div class="border-t border-surface-300 pt-4">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100"
                    @click="handleLogout"
                >
                    <UiIcon :name="IconList.SignOut" class="text-lg" />
                    <span>Logout</span>
                </button>
            </div>
        </template>
    </UiDrawer>
</template>
