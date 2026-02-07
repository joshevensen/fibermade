<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppMobileDrawer from '@/components/AppMobileDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { IconList } from '@/composables/useIcon';
import { useStoreNavigation } from '@/composables/useNavigation';
import { toUrl, urlIsActive } from '@/lib/utils';
import { logout } from '@/routes';
import type { BreadcrumbItemType, NavItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ConfirmPopup from 'primevue/confirmpopup';
import Toast from 'primevue/toast';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    pageTitle?: string;
}

withDefaults(defineProps<Props>(), {});

const navItems = useStoreNavigation();
const mobileDrawerVisible = ref(false);
const page = usePage();

const currentUrl = computed(() => page.url);

function isActive(item: { href: NonNullable<NavItem['href']> }) {
    return urlIsActive(toUrl(item.href), currentUrl.value);
}

function handleLogout() {
    router.post(logout.url());
}
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />

    <div class="flex min-h-screen flex-col">
        <!-- Header -->
        <header
            class="flex h-12 w-full items-center justify-between border-b border-surface-200 bg-surface-100 px-4"
        >
            <div class="flex items-center gap-4">
                <!-- Mobile: Menu toggle -->
                <UiButton
                    :icon="IconList.Menu"
                    text
                    class="md:hidden!"
                    @click="mobileDrawerVisible = !mobileDrawerVisible"
                />

                <!-- Logo -->
                <Link href="/store">
                    <AppLogo variant="full" class="h-8" />
                </Link>

                <!-- Nav -->
                <nav class="hidden items-center gap-6 md:flex">
                    <Link
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'text-sm font-medium transition-colors',
                            isActive(item)
                                ? 'text-primary'
                                : 'text-surface-600 hover:text-surface-900',
                        ]"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </div>

            <!-- Logout -->
            <UiButton
                :icon="IconList.SignOut"
                text
                class="hidden! md:flex!"
                @click="handleLogout"
            />
        </header>

        <!-- Page Content -->
        <main class="flex-1 px-4 pt-6 pb-8">
            <slot />
        </main>

        <!-- Mobile Drawer -->
        <AppMobileDrawer
            v-model:visible="mobileDrawerVisible"
            :nav-items="navItems"
        />
    </div>

    <!-- Global Components -->
    <ConfirmPopup />
    <Toast />
</template>
