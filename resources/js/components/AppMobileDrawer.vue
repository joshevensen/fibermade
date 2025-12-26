<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import AppLogo from '@/components/AppLogo.vue';
import { urlIsActive, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { logout } from '@/routes';

interface Props {
    visible: boolean;
    navItems: Array<{
        title: string;
        href: NonNullable<NavItem['href']>;
        icon?: string;
    }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

const page = usePage();
const currentUrl = computed(() => page.url);

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
            <AppLogo/>
        </template>

        <nav class="space-y-1 px-2">
            <Link
                v-for="item in navItems"
                :key="toUrl(item.href)"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-lg py-2 px-3 text-sm font-bold transition-colors hover:bg-surface-200',
                ]"
                @click="closeDrawer"
            >
                <i :class="[
                    item.icon,
                    isActive(item) ? 'text-primary' : 'text-surface-400',
                ]" />
                <span 
                    :class="[
                        isActive(item) ? 'text-primary' : 'text-surface-500', 
                        'flex-1'
                    ]"
                >{{ item.title }}</span>
            </Link>
        </nav>

        <template #footer>
            <div class="border-t border-surface-300 pt-4">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100"
                    @click="handleLogout"
                >
                    <i class="pi pi-sign-out text-lg" />
                    <span>Logout</span>
                </button>
            </div>
        </template>
    </UiDrawer>
</template>

