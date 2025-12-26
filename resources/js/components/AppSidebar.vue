<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { urlIsActive, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
</script>

<template>
    <aside
        :class="[
            'sticky top-0 hidden h-screen flex-col border-r border-surface-200 bg-surface-50 transition-all duration-300 lg:flex',
            collapsed ? 'w-16' : 'w-52',
        ]"
    >
        <div class="flex h-16 items-center justify-center px-4">
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
                        'group flex items-center gap-3 rounded-lg py-2 px-3 text-sm font-medium transition-colors',
                        isActive(item)
                            ? 'text-primary'
                            : 'text-gray-700 hover:bg-gray-100',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <i :class="[item.icon, 'text-lg']" />
                    <span v-if="!collapsed" class="flex-1">{{ item.title }}</span>
                </Link>
            </nav>
        </div>

        <div class="p-4">
            <UiButton
                :icon="collapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-left'"
                text
                class="w-full justify-center"
                @click="toggleCollapse"
            />
        </div>
    </aside>
</template>

