<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { IconList } from '@/composables/useIcon';
import { urlIsActive, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { logout } from '@/routes';

interface Props {
    collapsed: boolean;
    navItems: Array<{
        title: string;
        href: NonNullable<NavItem['href']>;
        icon?: string;
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
            collapsed ? 'w-16' : 'w-52',
        ]"
    >
        <div class="flex h-12 items-center justify-center px-4">
            <AppLogo
                v-if="collapsed"
                variant="icon"
                class="size-8"
            />
            <AppLogo
                v-else
                variant="full"
            />
        </div>

        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-2">
                <Link
                    v-for="item in navItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg py-2 px-3 text-sm font-bold transition-colors hover:bg-surface-200',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <i :class="[
                        item.icon,
                        isActive(item) ? 'text-primary' : 'text-surface-400',
                    ]" />
                    <span 
                        v-if="!collapsed" 
                        :class="[
                            isActive(item) ? 'text-primary' : 'text-surface-500', 
                            'flex-1'
                        ]"
                    >{{ item.title }}</span>
                </Link>
            </nav>
        </div>

        <div class="flex justify-between p-4">
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

