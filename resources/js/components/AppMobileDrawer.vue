<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiAvatar from '@/components/ui/UiAvatar.vue';
import { urlIsActive, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit as profileEdit } from '@/routes/profile';
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
const user = computed(() => page.props.auth.user);
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
            <div class="flex items-center gap-3 border-b pb-4">
                <UiAvatar
                    :label="user?.initials"
                    :image="user?.avatar"
                    size="large"
                    shape="circle"
                />
                <div class="flex flex-col">
                    <span class="font-semibold">{{ user?.name }}</span>
                    <span class="text-sm text-gray-600">{{ user?.email }}</span>
                </div>
            </div>
        </template>

        <nav class="space-y-1">
            <Link
                v-for="item in navItems"
                :key="toUrl(item.href)"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    isActive(item)
                        ? 'border-l-4 border-primary bg-primary/10 text-primary'
                        : 'text-gray-700 hover:bg-gray-100',
                ]"
                @click="closeDrawer"
            >
                <i :class="[item.icon, 'text-lg']" />
                <span>{{ item.title }}</span>
            </Link>
        </nav>

        <template #footer>
            <div class="border-t pt-4">
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

